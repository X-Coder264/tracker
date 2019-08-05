import TextListItem from 'cmf/js/listElements/text';
import LinkListItem from 'cmf/js/listElements/link';
import DateTimeListItem from 'cmf/js/listElements/dateTime';
import ContextMenu from 'cmf/js/listElements/contextMenu';
import TextInput from 'cmf/js/formElements/text';
import TextareaFormElement from 'cmf/js/formElements/textarea';
import ExternalAdminFormElement from 'cmf/js/formElements/externalAdmin';
import sortGenerator from '../helpers/sortGenerator';

export default {

    resourceName: 'news',

    includeApiData: {
        index: ['author'],
        edit: ['author']
    },

    setupList({list}) {

        this.addCreateControl('Create new news');

        //--------------------------------------------------------------
        // Filters
        //--------------------------------------------------------------

        list.addFilter(TextInput, {
            name: 'subject',
            label: 'Subject'
        });

        list.addFilter(ExternalAdminFormElement, {
            name: 'authorId',
            relation: {type: 'hasOne', resourceName: 'users'},
            label: 'Author',
            mapCaptionTo: 'name'
        });

        //--------------------------------------------------------------
        // Sorts
        //--------------------------------------------------------------

        list.addSort(sortGenerator([
            'ID -> id',
            'Updated at -> updated_at'
        ]));

        //--------------------------------------------------------------
        // Mass action
        //--------------------------------------------------------------

        list.addMassAction([{
            caption: 'Delete',
            action: model => model.destroy(),
            confirm: true
        }]);

        //--------------------------------------------------------------
        // List items
        //--------------------------------------------------------------

        list.addItem(TextListItem, {
            caption: 'ID',
            mapTo: 'id'
        });

        list.addItem(LinkListItem, {
            caption: 'Subject',
            mapTo: 'subject',
            action: 'editItem'
        });

        list.addItem(TextListItem, {
            caption: 'Author',
            mapTo: 'author.name'
        });

        list.addItem(DateTimeListItem, {
            caption: 'Created at',
            mapTo: 'created_at'
        });

        list.addItem(DateTimeListItem, {
            caption: 'Updated at',
            mapTo: 'updated_at'
        });

        list.addItem(ContextMenu, {
            caption: 'Actions',
            items: [
                {caption: 'Edit', action: 'editItem'},
                {caption: 'Delete', action: 'deleteItem', confirm: true}
            ]
        });

    },

    setupEdit: function({edit, method, resourceModel}) {

        this.addToIndexControl().addSaveControl();

        edit.addField(TextInput, {
            label: 'Subject',
            name: 'subject'
        });

        edit.addField(TextareaFormElement, {
            label: 'Text',
            name: 'text'
        });

    }

};
