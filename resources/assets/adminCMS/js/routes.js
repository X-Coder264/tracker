module.exports = function(router) {

    router.route('', 'dashboard', {uses: 'Users@index'});
    router.resource('users');
    router.resource('locales');
    router.resource('torrents');

};
