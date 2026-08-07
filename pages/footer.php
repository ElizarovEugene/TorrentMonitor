<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
    $.ajaxPrefilter(function (options) {
        if (options.type && options.type.toUpperCase() === 'POST') {
            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            if (options.data instanceof FormData) {
                options.data.append('csrf_token', token)
            } else if (typeof options.data === 'string') {
                options.data += (options.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(token)
            } else {
                options.data = $.extend({}, options.data, { csrf_token: token })
            }
        }
    })
</script>
<script src="assets/js/scripts.min.js"></script>
</body>
</html>
