import AdminDefault from 'cmf/js/layouts/adminDefault';

export default router => {

    router.addRoutes([{
        path: '/',
        name: 'users',
        component: AdminDefault,
        props: route => ({
            controller: {
                name: 'Users',
                method: 'index',
                params: route.params,
                query: route.query
            }
        })
    }]);

    router.resource('users');
    router.resource('locales');
    router.resource('torrents');

};
