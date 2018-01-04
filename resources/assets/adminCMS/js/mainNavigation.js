import BaseMainNavigation from 'trim-cmf/src/js/components/baseMainNavigation';
import bootData from 'trim-cmf/src/js/library/bootData';

module.exports = BaseMainNavigation.extend({

    getNavigationItems: function(router) {
        return [
            {
                name: 'Users', alias: 'users', url: router.url('resource.users.index')
            },
            {
                name: 'Locales', alias: 'locales', url: router.url('resource.locales.index')
            },
            {
                name: 'Torrents', alias: 'torrents', url: router.url('resource.torrents.index')
            }
        ];
    },

    getUserNavigationItems: function(router) {
        return [
            {
                name: 'Show search <span style="opacity: 0.4;">(Shift + l)</span>',
                action: mainNavigation => { mainNavigation.showSearch().close(); }
            }
        ];
    },

    getProjectCaption: function() {
        return bootData('projectCaption', 'Tracker');
    },

    getUserCaption: function() {
        return bootData('currentUser.caption', 'Admin user');
    }

});

