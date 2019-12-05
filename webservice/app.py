from flask import Flask, jsonify, abort, make_response, request
from codeprocessor import *
import clang.cindex

app = Flask(__name__)

clang.cindex.Config.set_library_file("/usr/lib/llvm-9/lib/libclang.so.1")

@app.route('/get_key_locations', methods=['POST'])
def gkl():
	temp = {
		'key_locations': work(request.json['source_code'], [], 1)
	}
	return jsonify({'result': temp}), 201

@app.route('/codeprocessor', methods=['POST'])
def cp():
	edit = request.json['edit']
	formatted_edit = list()
	for line in edit.split('\n'):
		formatted_edit.append(line)
		print (line)
	temp = work(request.json['source_code'], formatted_edit, 2)
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
	return jsonify(result), 201

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=80, debug=True)
