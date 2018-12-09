import app from 'cmf/js/app';
import services from './services';
import routes from './routes';
import translations from 'cmf/js/lang/english';
import '../scss/main.scss';

app.setBootData(window.bootData)
    .registerServices(services)
    .registerRoutes(routes)
    .loadTranslations(translations, 'en')
    .start();
