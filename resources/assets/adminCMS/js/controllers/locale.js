import TextListItem from 'cmf/js/listElements/text';
import LinkListItem from 'cmf/js/listElements/link';

export default {

    resourceName: 'locales',

    setupList({list}) {

        //this.addCreateControl('Create new locale');

        list.addItem(TextListItem, {
            caption: 'ID',
            mapTo: 'id'
        });

        list.addItem(LinkListItem, {
            caption: 'Locale',
            mapTo: 'locale',
            action: 'editItem'
        });

        list.addItem(TextListItem, {
            caption: 'Locale (short)',
            mapTo: 'locale-short'
        });

    },

    setupEdit: function({edit, method, resourceModel}) {

        //this.addToIndexControl().addSaveControl();

    }

};
