var STUDIP = STUDIP || {};
STUDIP.AufgabenConfig = STUDIP.AufgabenConfig || {};
var task_id;
var dialog;

jQuery(document).ready(function() {
    jQuery(function () {
        STUDIP.Aufgaben.Permissions.initialize();
        STUDIP.Files.filesapp.folders = [];
        STUDIP.Files.filesapp.removeFile = () => {};
    });

    (function() {
        var origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function() {
            this.addEventListener('load', function() {
                if (this.responseURL.includes(STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/file/upload/")) {
                    dialog = $('button[title="Schließen"]');

                    $.ajax(STUDIP.URLHelper.getURL('plugins.php/aufgabenplugin/index/upload_zip/'
                            + task_id + '/'
                            + JSON.parse(this.response).added_files[0].id), {
                        method: 'POST',
                        error: function(error) {
                            console.log(error);
                        }
                    })
                    .done(function() {
                        dialog.click();
                    });
                }
            });
            origOpen.apply(this, arguments);
        };
      })();
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
                url: STUDIP.URLHelper.getURL('plugins.php/aufgabenplugin/user/search'),
                dataType: 'json',
                data: function (params) {
                    return {
                        term: params.term, // search term
                    };
                },
                processResults: function (data) {
                    let arr = []
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

        let self = this;
        $('#add-permission').click(function(){
            self.add();
        })
    },

    add: function() {
        let self = this,
            data_user = $("#permissions select[name=search]").select2('data')[0];
            data_perm = $('#permissions select[name=permission]').select2('data')[0];

        if (data_user === undefined || data_user === null || data_user.id === "") {
            $('#permissions .error').hide()
                .html('Bitte suchen Sie zuerst nach einem/r Nutzer/in, dem/der eine Berechtigung eingeräumt werden soll!'.toLocaleString())
                .show('highlight');
            return;
        }

        let data = {
            user:       data_user.id,
            fullname:   data_user.text,
            perm:       data_perm.id,
            permission: data_perm.text
        }

        $('#permissions .error').hide();


        // store the new permission
        $.ajax(STUDIP.URLHelper.getURL('plugins.php/aufgabenplugin/index/add_permission/' + $('#edit-permissions-form').attr('data-task-user-id')), {
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
        let template = STUDIP.Aufgaben.getTemplate('permission'),
            self = this;

        $('#permission_list').append(template(data)).find('div:last-child img').click(function() {
            self.delete(data.user);
            $(this).parent().parent().remove();
        });
    },

    delete: function(user) {
        $.ajax(STUDIP.URLHelper.getURL('plugins.php/aufgabenplugin/index/delete_permission/' + $('#edit-permissions-form').attr('data-task-user-id')), {
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
