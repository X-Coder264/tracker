<!DOCTYPE html>
<html>
<head lang="en">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>Admin CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui"/>
    <meta name="theme-color" content="#ffffff">

</head>
<body>

<script>
    window.bootData = {
        baseUrl: '/cms/',
        baseApiUrl: '/cms/api/',
        currentUser: {
            caption: '{{ $user->email }}'
        },
        projectCaption: '{{ $projectName }}',
        resourceToApiMap: {
            users: 'users',
            locales: 'locales',
            torrents: 'torrents',
        },
        enumerations: {!! $enumerations !!}
    };
</script>


<script async src="{{ asset('admin/main.js') }}"></script>

</body>
</html>
