def decipher(ciphertext):
    for offset in range(256): # trying all possible values for offset
        plaintext = ""
        for cipher_char in ciphertext:
            plain_char = 227 * (cipher_char - offset) % 256
            plaintext += chr(plain_char)
        if "Securinets" in plaintext:
            print(plaintext)
            break

print(decipher([202, 16, 122, 192, 95, 60, 51, 16, 245, 42, 130, 149, 17, 106, 140, 78, 26, 132, 211, 149, 78, 17, 176, 132, 255, 78, 96, 202, 24]))

