##Image Optimizer
A simple web application to upload, convert, and optimize images to WebP format and download them as a ZIP archive. Built with PHP and Bootstrap 5.

Features
-Upload multiple images at once (JPEG, PNG, GIF)
-Convert images to WebP format
-Set custom WebP quality (10–100%)
-Real-time progress bar while processing
-Download all optimized images in a ZIP file
-Clean and modern Bootstrap-based UI


Running Locally with XAMPP
To run this project on your local XAMPP setup:
1.Copy Project Folder
2.Place the project folder inside C:\xampp\htdocs\project-folder.
3.Enable File Uploads
4.Make sure PHP allows file uploads. Check php.ini:
file_uploads = On
upload_max_filesize = 20M
post_max_size = 25M
5.Restart Apache after changes.
6.Folder Permissions
7.Ensure the downloads folder exists and is writable:
C:\xampp\htdocs\your-project-folder\downloads
Windows usually allows this by default, but if needed, right-click → Properties → Security → Give write permissions.

Required PHP Extensions
-Make sure the following PHP extensions are enabled in XAMPP:
-zip (for creating ZIP files)
-gd (for image processing)




Upload images, set WebP quality, and click Optimize. The ZIP file will be generated in downloads.
