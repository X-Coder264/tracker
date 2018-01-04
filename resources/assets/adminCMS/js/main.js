import app from 'trim-cmf/src/js/app';
import services from 'js/services';
import routes from 'js/routes';
import translations from 'trim-cmf/src/js/lang/english';
import '../scss/main.scss';

app.setBootData(window.bootData)
    .registerServices(services)
    .registerRoutes(routes)
    .loadTranslations(translations, 'en')
    .start();
