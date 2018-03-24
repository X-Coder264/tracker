import BaseResource from 'trikoder-cmf-ui/src/js/controllers/baseResource';
import TextListItem from 'trikoder-cmf-ui/src/js/listElements/text';
import LinkListItem from 'trikoder-cmf-ui/src/js/listElements/link';
import DateTimeListItem from 'trikoder-cmf-ui/src/js/listElements/dateTime';
import ContextMenu from 'trikoder-cmf-ui/src/js/listElements/contextMenu';
import TextInput from 'trikoder-cmf-ui/src/js/formElements/text';
import sortGenerator from '../helpers/sortGenerator';
import ExternalAdmin from 'trikoder-cmf-ui/src/js/formElements/externalAdmin';
import TextareaInput from 'trikoder-cmf-ui/src/js/formElements/textarea';

module.exports = BaseResource.extend({

    resourceName: 'torrents',

    includeApiData: {
        index: ['uploader'],
        edit: ['uploader']
    },

    setupList: function(listHandler) {

        //this.addCreateControl('Create new torrent');

        //--------------------------------------------------------------
        // Filters
        //--------------------------------------------------------------

        listHandler.addFilter(TextInput, {
            name: 'id',
            label: 'ID'
        });

        listHandler.addFilter(TextInput, {
            name: 'name',
            label: 'Name'
        });

        listHandler.addFilter(ExternalAdmin, {
            name: 'uploader',
            label: 'Uploader',
            mapCaptionTo: 'name',
            relation: {type: 'hasOne', resourceName: 'users'}
        });

        listHandler.addFilter(TextInput, {
            name: 'minimumSize',
            label: 'Size in MB (greater than)'
        });

        listHandler.addFilter(TextInput, {
            name: 'maximumSize',
            label: 'Size in MB (less than)'
        });

        listHandler.addFilter(TextInput, {
            name: 'slug',
            label: 'Slug'
        });

        //--------------------------------------------------------------
        // Sorts
        //--------------------------------------------------------------

        listHandler.addSort(sortGenerator([
            'ID -> id',
            'Created at -> created-at',
            'Updated at -> updated-at'
        ]));

        //--------------------------------------------------------------
        // Mass action
        //--------------------------------------------------------------

        listHandler.addMassAction([{
            caption: 'Delete',
            action: model => model.destroy(),
            confirm: true
        }]);

        //--------------------------------------------------------------
        // List items
        //--------------------------------------------------------------

        listHandler.addItem(TextListItem, {
            caption: 'ID',
            mapTo: 'id'
        });

        listHandler.addItem(LinkListItem, {
            caption: 'Name',
            mapTo: 'name',
            action: 'editItem'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Size',
            mapTo: 'size'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Slug',
            mapTo: 'slug'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Uploader',
            mapTo: 'uploader.name'
        });

        listHandler.addItem(DateTimeListItem, {
            caption: 'Created at',
            mapTo: 'created-at'
        });

        listHandler.addItem(DateTimeListItem, {
            caption: 'Updated at',
            mapTo: 'updated-at'
        });

        listHandler.addItem(ContextMenu, {
            caption: 'Actions',
            items: [
                {caption: 'Edit torrent', action: 'editItem'},
                {caption: 'Delete', action: 'deleteItem', confirm: true}
            ]
        });

    },

    setupEdit: function(editHandler, method, id) {

        this.addToIndexControl().addSaveControl();

        editHandler.addField(TextInput, {
            label: 'Name',
            name: 'name'
        });

        editHandler.addField(TextInput, {
            label: 'Size',
            name: 'size',
            readOnly: true,
        });

        editHandler.addField(TextareaInput, {
            label: 'Description',
            name: 'description'
        });

        editHandler.addField(ExternalAdmin, {
            name: 'uploader',
            label: 'Uploader',
            mapCaptionTo: 'name',
            relation: {type: 'hasOne', resourceName: 'users'}
        });

    }

});
