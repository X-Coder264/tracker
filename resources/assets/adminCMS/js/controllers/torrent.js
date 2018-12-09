import TextListItem from 'cmf/js/listElements/text';
import LinkListItem from 'cmf/js/listElements/link';
import DateTimeListItem from 'cmf/js/listElements/dateTime';
import ContextMenu from 'cmf/js/listElements/contextMenu';
import TextInput from 'cmf/js/formElements/text';
import sortGenerator from '../helpers/sortGenerator';
import ExternalAdmin from 'cmf/js/formElements/externalAdmin';
import TextareaInput from 'cmf/js/formElements/textarea';

export default {

    resourceName: 'torrents',

    includeApiData: {
        index: ['uploader'],
        edit: ['uploader']
    },

    setupList({list}) {

        //this.addCreateControl('Create new torrent');

        //--------------------------------------------------------------
        // Filters
        //--------------------------------------------------------------

        list.addFilter(TextInput, {
            name: 'id',
            label: 'ID'
        });

        list.addFilter(TextInput, {
            name: 'name',
            label: 'Name'
        });

        list.addFilter(ExternalAdmin, {
            name: 'uploader',
            label: 'Uploader',
            mapCaptionTo: 'name',
            relation: {type: 'hasOne', resourceName: 'users'}
        });

        list.addFilter(TextInput, {
            name: 'minimumSize',
            label: 'Size in MB (greater than)'
        });

        list.addFilter(TextInput, {
            name: 'maximumSize',
            label: 'Size in MB (less than)'
        });

        list.addFilter(TextInput, {
            name: 'slug',
            label: 'Slug'
        });

        //--------------------------------------------------------------
        // Sorts
        //--------------------------------------------------------------

        list.addSort(sortGenerator([
            'ID -> id',
            'Created at -> created-at',
            'Updated at -> updated-at'
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
            caption: 'Name',
            mapTo: 'name',
            action: 'editItem'
        });

        list.addItem(TextListItem, {
            caption: 'Size',
            mapTo: 'size'
        });

        list.addItem(TextListItem, {
            caption: 'Slug',
            mapTo: 'slug'
        });

        list.addItem(TextListItem, {
            caption: 'Uploader',
            mapTo: 'uploader.name'
        });

        list.addItem(DateTimeListItem, {
            caption: 'Created at',
            mapTo: 'created-at'
        });

        list.addItem(DateTimeListItem, {
            caption: 'Updated at',
            mapTo: 'updated-at'
        });

        list.addItem(ContextMenu, {
            caption: 'Actions',
            items: [
                {caption: 'Edit torrent', action: 'editItem'},
                {caption: 'Delete', action: 'deleteItem', confirm: true}
            ]
        });

    },

    setupEdit: function({edit, method, resourceModel}) {

        this.addToIndexControl().addSaveControl();

        edit.addField(TextInput, {
            label: 'Name',
            name: 'name'
        });

        edit.addField(TextInput, {
            label: 'Size',
            name: 'size',
            readOnly: true
        });

        edit.addField(TextareaInput, {
            label: 'Description',
            name: 'description'
        });

        edit.addField(ExternalAdmin, {
            name: 'uploader',
            label: 'Uploader',
            mapCaptionTo: 'name',
            relation: {type: 'hasOne', resourceName: 'users'}
        });

    }

};
