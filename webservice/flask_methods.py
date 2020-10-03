from subprocess import TimeoutExpired
from code_processor import CodeProcessor
from key_locations import KeyLocation


def extract_key_locations(code):
    cp = CodeProcessor(code)
    key_locations = cp.get_key_locations()
    print(cp.code)
    print(key_locations)
    return key_locations


def generate_variation(code, edit):
    cp = CodeProcessor(code)
    print(cp.code)
    print(edit)
    key_locations = cp.get_key_locations()
    output = {}
    output_hash = set()
    timed_out = set()
    return_list = list()

    try:
        return_list.append({
            'difficulty': cp.get_complexity(),
            'new_source_code': cp.code,
            'output': cp.get_output()
        })
        output_hash.add(hash(cp.code))
    except TimeoutExpired:
        print('Timed out for ' + cp.filename)
        timed_out.add(hash(cp.code))

    for i in range(100):
        if len(output) >= 30:
            break

        new_variation = list()
        key_loc = list()
        for (idx, key_location) in enumerate(key_locations):
            # This can be put in other place i.e improved
            kl = KeyLocation(key_location)
            kl.set_extra_info(edit[idx])
            key_loc.append(kl)
            new_variation.append(kl.generate_variation())
        new_cp = CodeProcessor(code, key_loc, new_variation)

        new_code = new_cp.code
        new_code_hash = hash(new_code)
        if new_code_hash in output_hash:
            continue

        if new_code_hash in timed_out:
            continue

        try:
            code_output = new_cp.get_output()
        except TimeoutExpired:
            print('Timed out for ' + new_cp.filename)
            timed_out.add(new_code_hash)
            continue

        output_hash.add(new_code_hash)

        if len(code_output) == 0:
            continue

        # Use hashing for keys instead of the code
        output[new_code] = code_output
        return_list.append({
            'difficulty': new_cp.get_complexity(),
            'new_source_code': new_code,
            'output': output[new_code]
        })

        print(new_code)

    # TODO VVV drop biggest difference variations
    return return_list
