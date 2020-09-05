from flask import Flask, jsonify, abort, make_response, request
from codeprocessor import *
import dbm
from os import path
import clang.cindex
import config

app = Flask(__name__)

clang.cindex.Config.set_library_file(config.CLANG_LIBRARY_FILE)


def init_dbm():
    dbpath = os.path.join(config.WORKING_CODES_DIR, config.DB_FILE)
    if path.exists(dbpath):
        return

    with dbm.open(dbpath, 'c') as db:
        db['MODULO'] = str(4.0)
        db['ADD'] = str(2.0)
        db['SUBTRACT'] = str(4.0)
        db['MULTIPLY'] = str(10.0)
        db['DIVIDE'] = str(10.0)
        db['FOR_STATEMENT'] = str(2.0)
        db['WHILE_STATEMENT'] = str(2.0)
        db['DO_STATEMENT'] = str(2.0)
        db['IF_STATEMENT'] = str(2.0)
        db['COMPARISON_OPERATORS'] = str(1.0)
        db['INCREMENT'] = str(1.0)
        db['LOGICAL_OPERATORS'] = str(1.0)


@app.route('/get_key_locations', methods=['POST'])
def gkl():
    temp = {
        'key_locations': get_key_locations(request.json['source_code'])
    }
    return jsonify({'result': temp}), 201


@app.route('/update_weights', methods=['POST'])
def update_weights():
    # TODO VVV implement
    values = {
        'MODULO': {'old_value': 4, 'new_value': 4.1},
        'ADD': {'old_value': 2, 'new_value': 2},
        'SUBTRACT': {'old_value': 4, 'new_value': 4},
        'MULTIPLY': {'old_value': 10, 'new_value': 10},
        'DIVIDE': {'old_value': 10, 'new_value': 10},
        'FOR_STATEMENT': {'old_value': 2, 'new_value': 2},
        'WHILE_STATEMENT': {'old_value': 2, 'new_value': 2},
        'DO_STATEMENT': {'old_value': 2, 'new_value': 2},
        'IF_STATEMENT': {'old_value': 2, 'new_value': 2},
        'COMPARISON_OPERATORS': {'old_value': 1, 'new_value': 1},
        'INCREMENT': {'old_value': 1, 'new_value': 1},
        'LOGICAL_OPERATORS': {'old_value': 1, 'new_value': 1}
    }
    return jsonify({'weights': values}), 201


@app.route('/accept_weights', methods=['POST'])
def accept_weights():
    with dbm.open(os.path.join(config.WORKING_CODES_DIR, config.DB_FILE), 'c') as db:
        for (key, value) in request.json.items():
            db[key] = str(float(value['new_value']))

    return 201


@app.route('/codeprocessor', methods=['POST'])
def codeprocessor():
    edit = request.json['edit']
    formatted_edit = list()
    for line in edit.split('\n'):
        formatted_edit.append(line)
        print(line)
    temp = generate_variation(request.json['source_code'], formatted_edit)
    result = []
    i = 0
    for element in temp:
        curr = {
            'id': i,
            'difficulty': element[0],
            'new_source_code': element[1],
            'output': element[2]
        }
        result.append(curr)
    print(result)
    return jsonify(result), 201


if __name__ == '__main__':
    # code = '#include <stdio.h>\n\nint main()\n{\n\xa0\xa0\xa0 int x=1048576;\n\xa0\xa0\xa0 x>>=10;\n\xa0\xa0\xa0 printf("%d\\n", x);\n\xa0\xa0\xa0 return 0;\n}\n'
    # formatted_edit = ['X', '7;8;9;11;12;13', 'X', 'X', '']
    # print(generate_variation(code, formatted_edit))
    init_dbm()
    app.run(host='0.0.0.0', port=config.SERVER_PORT, debug=config.SERVER_IS_DEBUG)
