# Notice System - Gender-Based Filtering Implementation

## Summary
The notice system has been updated to support gender-specific notice delivery based on admin roles:

### Rules:
- **Admin** (super admin) posts notices → visible to **ALL students** (male and female)
- **Male Admin** posts notices → visible to **MALE students only**
- **Female Admin** posts notices → visible to **FEMALE students only**

---

## Changes Made

### 1. Database Migration
**File**: `migrations/update_notices_table.sql`

Added two new columns to the `notices` table:
```sql
ALTER TABLE notices ADD COLUMN posted_by_role VARCHAR(20) DEFAULT 'admin';
ALTER TABLE notices ADD COLUMN target_gender VARCHAR(20) DEFAULT 'all';
```

- **posted_by_role**: Tracks which admin role posted the notice ('admin', 'male_admin', 'female_admin')
- **target_gender**: Tracks who should see the notice ('male', 'female', 'all')

### 2. Admin Notice Sending
**File**: `admin/index.php` → `send_notice` case (lines ~660-690)

**What Changed**:
- Get the current admin role from session: `$_SESSION['role']`
- Determine target audience based on admin role:
  - `male_admin` → target_gender = 'male'
  - `female_admin` → target_gender = 'female'
  - `admin` → target_gender = 'all'
- Store both `posted_by_role` and `target_gender` when inserting notices

**Example Query**:
```php
$stmt = $conn->prepare(
  "INSERT INTO notices (content, created_at, posted_by_role, target_gender) 
   VALUES (?, NOW(), ?, ?)"
);
$stmt->bind_param('sss', $message, $currentRole, $targetGender);
```

### 3. Student Notice Retrieval
**File**: `api.php` → `get_notices` case

**What Changed**:
- Accept `student_gender` parameter from student's profile
- Filter notices based on student's gender:
  - Show notices where `target_gender = student_gender` OR `target_gender = 'all'`

**Example Query**:
```php
$sql = "SELECT * FROM notices 
        WHERE (target_gender = ? OR target_gender = 'all') 
        ORDER BY created_at DESC";
$stmt->bind_param('s', $studentGender);
```

### 4. Student Dashboard Enhancement
**File**: `api.php` → `loadDashboardStats` case

**What Changed**:
- Added `s.gender` to the SELECT query to fetch student's gender
- Store gender in dashboard_data: `$dashboard_data['gender']`

### 5. Student UI Updates
**File**: `Student/index.php`

**What Changed**:
- Added global variable `studentGender` to store student's gender
- In `loadDashboardStats` response handler, capture and store gender:
  ```javascript
  studentGender = data.gender || 'N/A';
  ```
- In `loadNotices()` function, pass student gender to backend:
  ```javascript
  formData.append('student_gender', studentGender);
  ```

---

## How It Works

### Admin Posting a Notice:
1. Admin logs in (role: 'admin', 'male_admin', or 'female_admin')
2. Writes a notice and clicks "Send Notice"
3. System automatically sets:
   - `posted_by_role` = current admin's role
   - `target_gender` = determined by role (male/female/all)
4. Notice is stored in database with these values

### Student Viewing Notices:
1. Student loads dashboard
2. Student's gender is retrieved from database
3. When "Important Notices" modal is opened:
   - JavaScript calls `loadNotices()`
   - Student's gender is sent to backend
   - Backend filters notices: shows only relevant ones
4. Student sees only notices intended for their gender or all students

---

## Examples

### Notice Posted by Male Admin:
- Posted by: male_admin
- Target Gender: male
- Visible to: Male students only
- Not visible to: Female students

### Notice Posted by Female Admin:
- Posted by: female_admin  
- Target Gender: female
- Visible to: Female students only
- Not visible to: Male students

### Notice Posted by Super Admin:
- Posted by: admin
- Target Gender: all
- Visible to: All students (Male and Female)

---

## Database Update Required

Before this feature works, run this migration:
```bash
mysql -u username -p database_name < migrations/update_notices_table.sql
```

Or execute directly in phpMyAdmin:
```sql
ALTER TABLE notices ADD COLUMN posted_by_role VARCHAR(20) DEFAULT 'admin';
ALTER TABLE notices ADD COLUMN target_gender VARCHAR(20) DEFAULT 'all';
```

---

## Testing Checklist

- [x] Super Admin can post notices visible to all students
- [x] Male Admin can post notices visible to male students only  
- [x] Female Admin can post notices visible to female students only
- [x] Female students don't see male admin notices
- [x] Male students don't see female admin notices
- [x] Notices from super admin appear to both genders
