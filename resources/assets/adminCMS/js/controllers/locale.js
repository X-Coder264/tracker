import BaseResource from 'trikoder-cmf-ui/src/js/controllers/baseResource';
import TextListItem from 'trikoder-cmf-ui/src/js/listElements/text';
import LinkListItem from 'trikoder-cmf-ui/src/js/listElements/link';

module.exports = BaseResource.extend({

    resourceName: 'locales',

    setupList: function(listHandler) {

        //this.addCreateControl('Create new locale');

        listHandler.addItem(TextListItem, {
            caption: 'ID',
            mapTo: 'id'
        });

        listHandler.addItem(LinkListItem, {
            caption: 'Locale',
            mapTo: 'locale',
            action: 'editItem'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Locale (short)',
            mapTo: 'locale-short'
        });

    },

    setupEdit: function(editHandler, method, id) {

        //this.addToIndexControl().addSaveControl();

    }

});
