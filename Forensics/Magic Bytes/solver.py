def add_jpeg_header(input_image_path, output_image_path):
    # This is the magic bytes of jpg image
    jpeg_header = b'\xFF\xD8\xFF\xE0'
    with open(input_image_path, 'rb') as input_file:
        image_data = input_file.read()

    # Add the JPEG magic bytes to the beginning of the corrupted image
    modified_image_data = jpeg_header + image_data

    # Write the modified image data to the output file
    with open(output_image_path, 'wb') as output_file:
        output_file.write(modified_image_data)
        print("JPEG header added successfully. Modified image saved to:", output_image_path)

# Usage
input_image_path = "corrupted.jpg"
output_image_path = "notcorrupted.jpg"
add_jpeg_header(input_image_path, output_image_path)
