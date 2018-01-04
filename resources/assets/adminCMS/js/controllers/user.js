import BaseResource from 'trim-cmf/src/js/controllers/baseResource';
import TextListItem from 'trim-cmf/src/js/listElements/text';
import LinkListItem from 'trim-cmf/src/js/listElements/link';
import DateTimeListItem from 'trim-cmf/src/js/listElements/dateTime';
import ContextMenu from 'trim-cmf/src/js/listElements/contextMenu';
import TextInput from 'trim-cmf/src/js/formElements/text';
import ExternalAdmin from 'trim-cmf/src/js/formElements/externalAdmin';
import SelectInput from 'trim-cmf/src/js/formElements/select';
import _ from 'underscore';
import bootData from 'trim-cmf/src/js/library/bootData';
import sortGenerator from '../helpers/sortGenerator';

module.exports = BaseResource.extend({

    resourceName: 'users',

    setupList: function(listHandler) {

        this.addCreateControl('Create new user');

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

        listHandler.addFilter(TextInput, {
            name: 'email',
            label: 'E-mail'
        });

        listHandler.addFilter(TextInput, {
            name: 'timezone',
            label: 'Timezone'
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
            caption: 'E-mail',
            mapTo: 'email'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Timezone',
            mapTo: 'timezone'
        });

        listHandler.addItem(TextListItem, {
            caption: 'Slug',
            mapTo: 'slug'
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
                {caption: 'Edit user', action: 'editItem'},
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
            label: 'E-mail',
            name: 'email'
        });

        editHandler.addField(TextInput, {
            label: 'Password',
            name: 'password'
        });

        editHandler.addField(SelectInput, {
            label: 'Timezone',
            name: 'timezone',
            selectOptions: _.map(bootData('enumerations.timezones'), (caption, value) => {
                return {caption: caption, value: caption};
            }),
            attributes: {inputWrapper: {className: 'selectType1 fullWidth'}}
        });

        editHandler.addField(ExternalAdmin, {
            name: 'locale',
            label: 'Locale',
            mapCaptionTo: 'locale',
            relation: {type: 'hasOne', resourceName: 'locales'}
        });

    }

});
