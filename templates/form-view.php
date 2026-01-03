<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<form id="imageUploadForm" enctype="multipart/form-data" style="max-width:1080px;margin:auto;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
    <input type="text" name="name" placeholder="Name" required style="flex:1; padding:8px;">
    
    <input type="text" id="tradeDatePicker" name="date" placeholder="Select Trade Date" required
           class="flatpickr" style="flex:1; padding:8px;" readonly>
    
    <input type="file" name="file" accept="image/*" required style="flex:1;">
    
    <button type="submit" style="padding:8px 15px;background:#0073aa;color:#fff;border:none;border-radius:4px;">Upload</button>
    
    <div id="uploadStatus" style="width:100%;margin-top:5px;color:#0073aa;"></div>
</form>

<hr style="margin:20px 0;">

<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#tradeDatePicker", {
        dateFormat: "Y-m-d",
        defaultDate: new Date()
    });
});

document.getElementById('imageUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData();

    const nameField = form.querySelector('input[name="name"]');
    const dateField = form.querySelector('input[name="date"]');
    const fileField = form.querySelector('input[name="file"]');

    if (!fileField.files.length) {
        document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ Please select an image file.</span>";
        return;
    }

    formData.append('file', fileField.files[0]);
    formData.append('name', nameField.value);
    formData.append('date', dateField.value);

    document.getElementById('uploadStatus').innerHTML = "⏳ Uploading...";

    try {
        const res = await fetch('https://image.rdalgo.in/wp-json/rdalgo/v1/upload', {
            method: 'POST',
            body: formData
        });

        const text = await res.text();
        try {
            const result = JSON.parse(text);
            if (result.success) {
                document.getElementById('uploadStatus').innerHTML = "<span style='color:green;'>✅ Uploaded</span>";
                form.reset();
                flatpickr("#tradeDatePicker", {
                    dateFormat: "Y-m-d",
                    defaultDate: new Date()
                });
                loadUploadedImages(); // Refresh table
            } else {
                document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ " + (result.message || 'Upload failed.') + "</span>";
            }
        } catch (err) {
            document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ Server error: " + text + "</span>";
        }

    } catch (error) {
        document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ Network error.</span>";
        console.error(error);
    }
});
</script>
