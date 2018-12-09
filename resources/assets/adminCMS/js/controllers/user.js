import TextListItem from 'cmf/js/listElements/text';
import LinkListItem from 'cmf/js/listElements/link';
import DateTimeListItem from 'cmf/js/listElements/dateTime';
import ContextMenu from 'cmf/js/listElements/contextMenu';
import TextInput from 'cmf/js/formElements/text';
import ExternalAdmin from 'cmf/js/formElements/externalAdmin';
import SelectInput from 'cmf/js/formElements/select';
import _ from 'underscore';
import bootData from 'cmf/js/library/bootData';
import sortGenerator from '../helpers/sortGenerator';

export default {

    resourceName: 'users',

    includeApiData: {
        index: ['locale'],
        edit: ['locale']
    },

    setupList({list}) {

        this.addCreateControl('Create new user');

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

        list.addFilter(TextInput, {
            name: 'email',
            label: 'E-mail'
        });

        list.addFilter(TextInput, {
            name: 'timezone',
            label: 'Timezone'
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
            caption: 'E-mail',
            mapTo: 'email'
        });

        list.addItem(TextListItem, {
            caption: 'Timezone',
            mapTo: 'timezone'
        });

        list.addItem(TextListItem, {
            caption: 'Slug',
            mapTo: 'slug'
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
                {caption: 'Edit user', action: 'editItem'},
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
            label: 'E-mail',
            name: 'email'
        });

        if (method === 'create') {
            edit.addField(TextInput, {
                label: 'Password',
                name: 'password'
            });
        }

        edit.addField(SelectInput, {
            label: 'Timezone',
            name: 'timezone',
            selectOptions: _.map(bootData('enumerations.timezones'), (caption, value) => {
                return {caption: caption, value: caption};
            }),
            attributes: {inputWrapper: {className: 'selectType1 fullWidth'}}
        });

        edit.addField(ExternalAdmin, {
            name: 'locale',
            label: 'Locale',
            mapCaptionTo: 'locale',
            relation: {type: 'hasOne', resourceName: 'locales'}
        });

    }

};
