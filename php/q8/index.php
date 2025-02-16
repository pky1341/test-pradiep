<!DOCTYPE html>
<html>
<head>
    <title>Secure Image Upload</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        .result { margin-top: 20px; padding: 10px; border-radius: 4px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        img { max-width: 100%; max-height: 300px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Secure Image Upload</h1>
    
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="image">Select an image to upload:</label>
            <input type="file" name="image" id="image" accept="image/*">
        </div>
        <button type="submit">Upload Image</button>
    </form>

    <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
        <div class="result <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($_GET['message']); ?>
            
            <?php if (isset($_GET['path']) && $_GET['status'] === 'success'): ?>
                <div>
                    <img src="<?php echo htmlspecialchars($_GET['path']); ?>" alt="Uploaded image">
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>