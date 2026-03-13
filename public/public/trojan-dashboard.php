
<!-- Additional script to auto‑select android platform for android_custom -->
<script>
document.querySelector('select[name="type"]').addEventListener('change', function() {
    if (this.value === 'android_custom') {
        // Set platform to android
        var platformSelect = document.querySelector('select[name="platform"]');
        for (var i = 0; i < platformSelect.options.length; i++) {
            if (platformSelect.options[i].value === 'android') {
                platformSelect.selectedIndex = i;
                break;
            }
        }
        // Set format to apk
        var formatSelect = document.querySelector('select[name="format"]');
        for (var i = 0; i < formatSelect.options.length; i++) {
            if (formatSelect.options[i].value === 'apk') {
                formatSelect.selectedIndex = i;
                break;
            }
        }
    }
});

// Trigger on page load if android_custom is pre‑selected
if (document.querySelector('select[name="type"]').value === 'android_custom') {
    var platformSelect = document.querySelector('select[name="platform"]');
    for (var i = 0; i < platformSelect.options.length; i++) {
        if (platformSelect.options[i].value === 'android') {
            platformSelect.selectedIndex = i;
            break;
        }
    }
    var formatSelect = document.querySelector('select[name="format"]');
    for (var i = 0; i < formatSelect.options.length; i++) {
        if (formatSelect.options[i].value === 'apk') {
            formatSelect.selectedIndex = i;
            break;
        }
    }
}
</script>
