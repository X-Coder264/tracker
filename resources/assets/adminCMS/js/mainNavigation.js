import bootData from 'cmf/js/library/bootData';

export default {

    getNavigationItems: router => [{
        caption: 'Users',
        key: 'users',
        routeName: 'resource.users.index',
        icon: 'user'
    }, {
        caption: 'Torrents',
        key: 'torrents',
        routeName: 'resource.torrents.index',
        icon: 'messageSquare'
    }, {
        caption: 'News',
        key: 'news',
        routeName: 'resource.news.index'
    }, {
        caption: 'Locales',
        key: 'locale',
        routeName: 'resource.locales.index',
        icon: 'alignLeft'
    }],

    getUserNavigationItems: router => [

        {
            name: 'Search <span style="opacity: 0.4;">(Shift + l)</span>',
            action: mainNavigation => mainNavigation.showSearch().close()
        }

    ],

    getProjectCaption: () => bootData('projectCaption', 'Tracker'),

    getUserCaption: () => bootData('currentUser.caption', 'Admin user')

};
