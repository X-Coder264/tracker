import MainNavigation from './mainNavigation';

export default {
    MainNavigation: () => MainNavigation,
    UsersController: () => import('./controllers/user'),
    LocalesController: () => import('./controllers/locale'),
    TorrentsController: () => import('./controllers/torrent'),
    NewsController: () => import('./controllers/news')
};
