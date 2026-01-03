<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<form id="imageUploadForm" enctype="multipart/form-data" style="max-width:1080px;margin:auto;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
    
    <select name="name" id="nameDropdown" required style="flex:1; padding:8px; height: 40px;">
        <option value="">Select Name...</option>
    </select>
    
    <input type="text" id="tradeDatePicker" name="date" placeholder="Select Trade Date" required
           class="flatpickr" style="flex:1; padding:8px; height: 40px;" readonly>
    
    <input type="file" name="file" accept="image/*" required style="flex:1; padding: 5px;">
    
    <button type="submit" style="padding:8px 15px;background:#0073aa;color:#fff;border:none;border-radius:4px; height: 40px; cursor: pointer;">Upload</button>
    
    <div id="uploadStatus" style="width:100%;margin-top:5px;color:#0073aa;"></div>
</form>

<hr style="margin:20px 0;">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const datePicker = flatpickr("#tradeDatePicker", {
        dateFormat: "Y-m-d",
        defaultDate: new Date(),
        onChange: function(selectedDates, dateStr, instance) {
            loadPendingNames(dateStr);
        }
    });

    // Initial load for today
    loadPendingNames(datePicker.input.value);

    // Listen for global delete events to refresh the dropdown (re-adding the deleted name)
    window.addEventListener('uim_refresh_names', function() {
        const currentDate = document.getElementById('tradeDatePicker').value;
        if(currentDate) {
            loadPendingNames(currentDate);
        }
    });
});

async function loadPendingNames(date) {
    const dropdown = document.getElementById('nameDropdown');
    
    // Show loading state
    dropdown.innerHTML = '<option value="">Loading...</option>';
    dropdown.disabled = true;

    try {
        const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
        const params = new URLSearchParams({
            action: 'fetch_pending_names',
            date: date
        });

        const res = await fetch(`${ajaxUrl}?${params.toString()}`);
        const result = await res.json();

        dropdown.innerHTML = '<option value="">Select Name...</option>';

        if (result.success && Array.isArray(result.data)) {
            if(result.data.length === 0) {
                 const option = document.createElement('option');
                 option.text = "No approved names pending";
                 dropdown.appendChild(option);
            } else {
                result.data.forEach(name => {
                    const option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    dropdown.appendChild(option);
                });
            }
        } else {
            console.error("Failed to fetch names or no names available.");
        }

    } catch (e) {
        console.error("Error fetching names:", e);
        dropdown.innerHTML = '<option value="">Error loading names</option>';
    } finally {
        dropdown.disabled = false;
    }
}

document.getElementById('imageUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData();

    const nameField = form.querySelector('select[name="name"]');
    const dateField = form.querySelector('input[name="date"]');
    const fileField = form.querySelector('input[name="file"]');

    if (!fileField.files.length) {
        document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ Please select an image file.</span>";
        return;
    }

    if (!nameField.value || nameField.value === "No approved names pending") {
        document.getElementById('uploadStatus').innerHTML = "<span style='color:red;'>❌ Please select a valid name.</span>";
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
                
                // Refresh dropdown to remove the uploaded name
                loadPendingNames(dateField.value);
                
                // Reset file input only (keep date)
                fileField.value = '';
                
                // Refresh table if the function exists
                if (typeof loadUploadedImages === "function") {
                    loadUploadedImages(); 
                }
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