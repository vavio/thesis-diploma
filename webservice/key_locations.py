import re
from random import choice, random
import string


class KeyLocation:
    re_value = re.compile(r'^-?\d+$')
    re_range = re.compile(r'^-?\d+:-?\d+$')
    re_exclude = re.compile(r'^\^-?\d')

    def __init__(self, kl):
        self.start_line = kl['start_line']
        self.start_column = kl['start_column']
        self.end_line = kl['end_line']
        self.end_column = kl['end_column']
        self.value = kl['value']
        self.location_type = kl['type']
        self.extra_info = ""

    def set_extra_info(self, line):
        self.extra_info = line

    @staticmethod
    def generate_numbers(options):
        splitted = options.split(",")
        numbers = list()
        excluded = set()
        for s in splitted:
            if KeyLocation.re_value.match(s):
                numbers.append(int(s))
            elif KeyLocation.re_range.match(s):
                splitted_range = s.split(':')
                for val in range(int(splitted_range[0]), int(splitted_range[1]) + 1):
                    numbers.append(val)
            elif KeyLocation.re_exclude.match(s):
                excluded.add(int(s[1:]))

        return [n for n in numbers if n not in excluded]

    def generate_variation(self):
        if self.extra_info == "":
            return self.value

        if self.location_type in {"binary_op", "unary_op", "logical"}:
            return choice(self.extra_info.split(";"))

        if self.location_type == "integer":
            numbers = self.generate_numbers(self.extra_info)
            return str(choice(numbers))

        if self.location_type == "float":
            temp = self.extra_info.split(":")
            minr = min(float(temp[0]), float(temp[1]))
            maxr = max(float(temp[0]), float(temp[1]))
            return str(round(random() * (maxr - minr) + minr, 2))

        if self.location_type in {"text", "character"}:

            splitted = self.extra_info.split(";")
            numbers = self.generate_numbers(splitted[0])
            if len(numbers) == 0:
                # it is intentionally -2 to calculate "" or '' in the value
                numbers = [len(self.value) - 2]

            characters = list()
            # leave out the range
            splitted = splitted[1:]
            for s in splitted:
                if s == "lowercase":
                    characters.extend(string.ascii_lowercase)
                elif s == "uppercase":
                    characters.extend(string.ascii_uppercase)
                elif s == "digits":
                    characters.extend(string.digits)
            random_text = ''.join([choice(characters) for _ in range(choice(numbers))])
            return ''.join([self.value[0], random_text, self.value[-1]])

        return self.value

