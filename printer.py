from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route("/print", methods=["POST"])
def print_receipt():

    data = request.json.get("data")
    share_name = request.json.get("shareName")

    if not data or not share_name:
        return jsonify({"success": False, "message": "Missing data"}), 400

    printer_path = r"\\localhost\\" + share_name

    try:
        with open(printer_path, "wb") as printer:
            printer.write(data.encode("latin1"))

        return jsonify({"success": True, "message": "Printed successfully"})

    except Exception as e:
        return jsonify({"success": False, "message": str(e)})

app.run(host="127.0.0.1", port=5000)