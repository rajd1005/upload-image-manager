<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div style="max-width:1000px;margin:auto;">
    <label><strong>Filter by Trade Date:</strong></label>
    <input type="text" id="filterDate" class="flatpickr" style="margin-bottom:15px;padding:6px 10px;" readonly>
</div>

<div id="imageGallery" style="overflow-x:auto;"></div>

<div id="imageModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#000000c9;align-items:center;justify-content:center;z-index:1000;">
    <div onclick="closeModal()" style="position:absolute;top:20px;right:20px;color:#fff;font-size:24px;cursor:pointer;">✖</div>
    <img id="modalImage" src="" style="max-width:90%;max-height:90%;">
</div>

<script>
function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.getElementById('modalImage').src = '';
}

function openModal(url) {
    document.getElementById('modalImage').src = url;
    document.getElementById('imageModal').style.display = 'flex';
}

async function loadUploadedImages() {
    const container = document.getElementById('imageGallery');
    container.innerHTML = "🔄 Loading...";

    const date = document.getElementById('filterDate').value;
    const timestamp = Date.now();

    if (!date) {
        container.innerHTML = "<p style='color:red;'>❌ Please select a date</p>";
        return;
    }

    try {
        const url = `https://image.rdalgo.in/wp-json/rdalgo/v1/images?start=${date}&end=${date}&_=${timestamp}`;
        const res = await fetch(url);
        const text = await res.text();
        console.log("📦 API response:", text);

        let images;
        try {
            images = JSON.parse(text);
        } catch (e) {
            container.innerHTML = "<p style='color:red;'>❌ Invalid response</p>";
            return;
        }

        container.innerHTML = '';

        if (!images || images.length === 0) {
            container.innerHTML = "<p>No images found for selected date.</p>";
            return;
        }

        const table = document.createElement('table');
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        table.innerHTML = `
            <thead>
                <tr style="background:#f0f0f0;">
                    <th style="padding:8px;border:1px solid #ccc;">Trade Date</th>
                    <th style="padding:8px;border:1px solid #ccc;">Name</th>
                    <th style="padding:8px;border:1px solid #ccc;">View</th>
                    <th style="padding:8px;border:1px solid #ccc;">Delete</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;

        images.forEach(img => {
            const row = document.createElement('tr');

            const tradeDate = new Date(img.trade_date + 'T00:00:00+05:30');
            const now = new Date();
            const istNow = new Date(now.getTime() + (330 - now.getTimezoneOffset()) * 60000);
            const istTodayStr = istNow.toISOString().slice(0, 10); // yyyy-mm-dd

            const currentUserRole = "<?php echo wp_get_current_user()->roles[0] ?? ''; ?>";
            const privilegedRoles = ['administrator', 'company_owner', 'manager'];
            const canAlwaysDelete = privilegedRoles.includes(currentUserRole);

            const canDelete = canAlwaysDelete || (img.trade_date === istTodayStr);

            row.innerHTML = `
                <td style="padding:8px;border:1px solid #ccc;text-align:center;">${img.trade_date}</td>
                <td style="padding:8px;border:1px solid #ccc;text-align:center;">${img.name}</td>
                <td style="padding:8px;border:1px solid #ccc;text-align:center;">
                    <button onclick="openModal('${img.image_url}')" style="padding:5px 10px;">View</button>
                </td>
                <td style="padding:8px;border:1px solid #ccc;text-align:center;">
                    ${canDelete
                        ? `<button onclick="deleteImage(${img.id})" style="padding:5px 10px;background:red;color:white;border:none;border-radius:3px;">Delete</button>`
                        : '<span style="color:#aaa;">Not allowed</span>'}
                </td>
            `;
            table.querySelector('tbody').appendChild(row);
        });

        container.appendChild(table);

    } catch (err) {
        container.innerHTML = "<p style='color:red;'>❌ Failed to load images</p>";
        console.error("Error:", err);
    }
}

async function deleteImage(id) {
    if (!confirm("Are you sure you want to delete this image?")) return;

    const res = await fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: "delete_uploaded_image", id })
    });

    const text = await res.text();
    try {
        const result = JSON.parse(text);
        alert(result.success ? "✅ Deleted" : "❌ Delete failed: " + (result.message || ''));
        if (result.success) {
            loadUploadedImages();
            // Trigger event to refresh the form dropdown (name reappears in list)
            window.dispatchEvent(new Event('uim_refresh_names'));
        }
    } catch (e) {
        alert("❌ Error: " + text);
    }
}

// Init Flatpickr & first load
document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#filterDate", {
        dateFormat: "Y-m-d",
        defaultDate: new Date(),
        onChange: loadUploadedImages
    });

    setTimeout(loadUploadedImages, 500);
});
</script>