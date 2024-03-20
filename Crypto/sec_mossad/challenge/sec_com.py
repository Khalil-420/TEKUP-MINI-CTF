import random

flag = private


def sec_communication(input_data):
  modulus = 256
  multiplier = 203
  offset = random.randint(1, modulus)
  encrypted_output = []
  
  for character in input_data:
    encrypted_character = (multiplier * ord(character) + offset) % modulus
    encrypted_output.append(encrypted_character)
  
  return encrypted_output

print(sec_communication(flag))

#[202, 16, 122, 192, 95, 60, 51, 16, 245, 42, 130, 149, 17, 106, 140, 78, 26, 132, 211, 149, 78, 17, 176, 132, 255, 78, 96, 202, 24]
