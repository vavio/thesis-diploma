from flask import Flask, jsonify, abort, make_response, request
from flask_methods import *
import dbm
from os import path
import clang.cindex
import config
import os

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
def get_key_locations():
    temp = {
        'key_locations': extract_key_locations(request.json['source_code'])
    }
    return jsonify({'result': temp}), 201


@app.route('/codeprocessor', methods=['POST'])
def codeprocessor():
    result = generate_variation(request.json['source_code'], request.json['edit'])
    print(result)
    return jsonify(result), 201


# TODO WIP this needs to be implemented
@app.route('/update_weights', methods=['POST'])
def update_weights():
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


if __name__ == '__main__':
    # code = '#include <stdio.h>\n#include <stdlib.h>\n#include <math.h>\n\nint main()\n{\n\xa0\xa0\xa0 for (int i=1; i<=6; i++)\n\xa0\xa0\xa0 {\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0 if (i%2==1) printf("%d",i);\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0 else printf("%d",i-3);\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0 if (i>12)\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0 {\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0 printf("%d%d",i%3, fabs(i/4));\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0 printf("%d%d%d%d",i/5, fabs(i%4), i--, --i);\n\xa0\xa0\xa0\xa0\xa0\xa0\xa0 }\n\xa0\xa0\xa0 }\n\xa0\xa0\xa0 //** printf("%d",a^2+b%3+c);\n\xa0\xa0\xa0 //\n\xa0\xa0\xa0 return 0;\n}\n\n'
    # get_key_locations(code)
    # formatted_edit = ["1:10,^5,100", "*;-;%;<;>;!=", "6", "++;--", "+;=;<=;>=;==", "2", "1", "+;<=;>;!=", "3", "12", "*;>=;==", "3", "4", "5", "+;<=;>", "4", "--", "--"]
    # print(generate_variation(code, formatted_edit))
    init_dbm()
    app.run(host='0.0.0.0', port=config.SERVER_PORT, debug=config.SERVER_IS_DEBUG)
