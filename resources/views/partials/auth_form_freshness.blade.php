@if(request()->routeIs('home', 'login', 'register', 'password.forgot', 'password.reset.form'))
<script data-auth-form-freshness>
// History restoration can bypass HTTP cache rules. Refresh only access forms,
// with a new GET: never replay a POST, send credentials or clear browser storage.
window.addEventListener('pageshow', function (event) {
    if (event.persisted) window.location.reload();
});
</script>
@endif
