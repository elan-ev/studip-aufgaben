var STUDIP = STUDIP || {};
STUDIP.AufgabenConfig = STUDIP.AufgabenConfig || {};

jQuery(document).ready(function() {
    jQuery(function () {
        STUDIP.Aufgaben.Permissions.initialize();
    });
});


STUDIP.Aufgaben = {
    getTemplate: _.memoize(function(name) {
        return _.template($("script." + name).html());
    })
}


STUDIP.Aufgaben.Permissions = {
    initialize: function() {
        $('#permissions select[name=search]').select2({
            width: 'copy',
            minimumInputLength: 3,
            escapeMarkup: function (m) {
                return m;
            },
            ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                url: STUDIP.URLHelper.getURL('plugins.php/' + STUDIP.AufgabenConfig.plugin_name + '/user/search'),
                dataType: 'json',
                data: function (params) {
                    return {
                        term: params.term, // search term
                    };
                },
                processResults: function (data) {
                    var arr = []
                    $.each(data, function (index, value) {
                        arr.push({
                            id: value.id,
                            text: value.text
                        })
                    })
                    return {results: arr, more: false};
                },
            },
        });

        $('#permissions select[name=permission]').select2({
            width: 'copy',
            minimumResultsForSearch: -1
        });

        var self = this;
        $('#add-permission').click(function(){
            self.add();
        })
    },

    add: function() {
        var self = this,
            data_user = $("#permissions select[name=search]").select2('data')[0];
            data_perm = $('#permissions select[name=permission]').select2('data')[0];

        if (data_user === undefined || data_user === null || data_user.id === "") {
            $('#permissions .error').hide()
                .html('Bitte suchen Sie zuerst nach einem/r Nutzer/in, dem/der eine Berechtigung eingeräumt werden soll!'.toLocaleString())
                .show('highlight');
            return;
        }

        var data = {
            user:       data_user.id,
            fullname:   data_user.text,
            perm:       data_perm.id,
            permission: data_perm.text
        }

        $('#permissions .error').hide();


        // store the new permission
        $.ajax(STUDIP.URLHelper.getURL('plugins.php/' + STUDIP.AufgabenConfig.plugin_name + '/index/add_permission/' + $('#edit-permissions-form').attr('data-task-user-id')), {
            method: 'POST',
            data: data,
            success: function() {
                self.addTemplate(data);
            },

            error: function(error) {
                $('#permissions .error').hide()
                    .html(error.statusText)
                    .show('highlight');
            }
        });


    },

    addTemplate: function(data) {
        var template = STUDIP.Aufgaben.getTemplate('permission'),
            self = this;

        $('#permission_list').append(template(data)).find('div:last-child img').click(function() {
            console.log($(this));
            self.delete(data.user);
            $(this).parent().parent().remove();
        });
    },

    delete: function(user) {
        $.ajax(STUDIP.URLHelper.getURL('plugins.php/' + STUDIP.AufgabenConfig.plugin_name + '/index/delete_permission/' + $('#edit-permissions-form').attr('data-task-user-id')), {
            method: 'POST',
            data: {user: user}
        });
    }
}


STUDIP.epp = {
    removeFile: function(id) {
        jQuery('#fileref_' + id).remove();
        jQuery.post(STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/file/delete/" + id,
            {}
        );
    },
};

STUDIP.Files.filesapp = {
    files: []
}
