from subprocess import TimeoutExpired
from code_processor import CodeProcessor
from key_locations import KeyLocation


def extract_key_locations(code):
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()

    print(key_locations)
    return key_locations


def generate_variation(code, edit):
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()
    output = {}
    return_list = list()
    for i in range(30):
        new_variation = list()
        key_loc = list()
        for (idx, key_location) in enumerate(key_locations):
            # This can be put in other place i.e improved
            kl = KeyLocation(key_location)
            kl.set_extra_info(edit[idx])
            key_loc.append(kl)
            new_variation.append(kl.generate_variation())
        new_cp = CodeProcessor(code, key_loc, new_variation)

        if new_cp.code in output:
            continue

        try:
            code_output = new_cp.get_output()
        except TimeoutExpired:
            print('Timed out for ' + new_cp.filename)
            continue

        if len(code_output) == 0:
            continue

        # Use hashing for keys instead of the code
        output[new_cp.code] = code_output
        return_list.append({
            'difficulty': new_cp.get_complexity(),
            'new_source_code': new_cp.code,
            'output': output[new_cp.code]
        })

        print(new_cp.code)
    return return_list
