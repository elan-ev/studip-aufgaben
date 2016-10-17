var STUDIP = STUDIP || {};
STUDIP.AufgabenConfig = STUDIP.AufgabenConfig || {};

jQuery(document).ready(function() {
    // jQuery('input[type=file]').bind('change', STUDIP.epp.addFile);
    jQuery(function () {
        jQuery('#fileupload').fileupload({
            dataType: 'json',
            add: function (e, data) {
                STUDIP.epp.file_id += 1;
                data.id = STUDIP.epp.file_id;
                STUDIP.epp.addFile(e, data);
            },
            done: function (e, data) {
                var files = data.result;

                if (typeof files.errors === "object") {
                    var errorTemplateData = {
                        message: json.errors.join("\n")
                    }
                    jQuery('#files_to_upload').before(STUDIP.epp.errorTemplate(errorTemplateData));
                } else {
                    _.each(files, function(file) {
                        var id = jQuery('#files_to_upload tr:first-child').attr('data-fileid');
                        jQuery('#files_to_upload tr[data-fileid=' + id + ']').remove();

                        var templateData = {
                            id        : file.id,
                            url       : file.url,
                            name      : file.name,
                            size      : Math.round((file.size / 1024) * 100) / 100,
                            date      : file.date,
                            seminar   : file.seminar_id,
                            user_url  : file.user_url,
                            user_name : file.user_name
                        }

                        jQuery('#uploaded_files').append(STUDIP.epp.uploadedFileTemplate(templateData));
                    });
                }
            },

            progress: function (e, data) {
                var kbs = parseInt(data._progress.bitrate / 8 / 1024);
                var progress = parseInt(data.loaded / data.total * 100, 10);
                var id = jQuery('#files_to_upload tr:first-child').attr('data-fileid');
                jQuery('#files_to_upload tr[data-fileid=' + id + '] progress').val(progress);
                jQuery('#files_to_upload tr[data-fileid=' + id + '] .kbs').html(kbs);
            },

            error: function(xhr, data) {
                var id = jQuery('#files_to_upload tr:first-child').attr('data-fileid');
                jQuery('#files_to_upload tr[data-fileid=' + id + '] td:nth-child(3)')
                            .html('Fehler beim Upload (' + xhr.status  + ': ' + xhr.statusText + ')');
                jQuery('#files_to_upload tr[data-fileid=' + id + '] td:nth-child(4)').html('');
                jQuery('#files_to_upload tr[data-fileid=' + id + '] td:nth-child(5)').html('');
                jQuery('#files_to_upload tr[data-fileid=' + id + '] td:nth-child(6)').html('');

                jQuery('#files_to_upload').append(jQuery('#files_to_upload tr[data-fileid=' + id + ']').remove());
            }
        });

        STUDIP.Aufgaben.Permissions.initialize();
    });

    // load templates
    STUDIP.epp.fileTemplate         = _.template(jQuery("script.file_template").html());
    STUDIP.epp.uploadedFileTemplate = _.template(jQuery("script.uploaded_file_template").html());
    STUDIP.epp.errorTemplate        = _.template(jQuery("script.error_template").html());
    STUDIP.epp.questionTemplate     = _.template(jQuery("script.confirm_dialog").html());
});


STUDIP.Aufgaben = {
    getTemplate: _.memoize(function(name) {
        return _.template($("script." + name).html());
    })
}


STUDIP.Aufgaben.Permissions = {
    initialize: function() {
        $('#permissions input[name=search]').select2({
            width: 'copy',
            minimumInputLength: 3,
            placeholder: "Nach Vorname und/oder Nachname suchen...".toLocaleString(),

            ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                url: STUDIP.URLHelper.getURL('plugins.php/' + STUDIP.AufgabenConfig.plugin_name + '/user/search'),
                dataType: 'json',
                data: function (term, page) {
                    return {
                        term: term
                    }
                },
                results: function (data, page) { // parse the results into the format expected by Select2.
                    return {results: data, more: false};
                }
            },

            formatResult: function (user) {
                return user.picture + ' ' + user.text;
            },

            formatSelection: function (user) {
                return user.text;
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
            data_user = $("#permissions input[name=search]").select2("data");
            data_perm = $('#permissions select[name=permission]').select2("data");

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
    files : {},
    maxFilesize: 0,
    fileTemplate: null,
    uploadedFileTemplate: null,
    errorTemplate: null,
    questionTemplate: null,
    file_id: 0,

    addFile: function(e, data) {
        // this is the first file for the current upload-list
        if (STUDIP.epp.file_id == 1) {
            jQuery('#files_to_upload').html('');
        }

        jQuery('#upload_button').removeClass('disabled');

        var file = data.files[0];
        STUDIP.epp.files[data.id] = data;

        var templateData = {
            id: data.id,
            name: file.name,
            error: file.size > STUDIP.epp.maxFilesize,
            size: Math.round((file.size / 1024) * 100) / 100
        }

        jQuery('#files_to_upload').append(STUDIP.epp.fileTemplate(templateData));

        if(file.type == 'image/png'
            || file.type == 'image/jpg'
            || file.type == 'image/gif'
            || file.type == 'image/jpeg') {

            var img = new Image();

            var reader = new FileReader();

            reader.onload = function (e) {
                img.src = e.target.result;
            }

            reader.readAsDataURL(file);

            jQuery('#files_to_upload tr:last-child td:first-child').append(img);
        }
    },

    removeUploadFile: function(id) {
        var files = STUDIP.epp.files[id];
        delete STUDIP.epp.files[id];

        _.each(files, function(file) {
            if (file.jqXHR) {
                file.jqXHR.abort();
            }
        });

        jQuery('#files_to_upload tr[data-fileid=' + id + ']').remove();
    },

    removeFile: function(seminar_id, id) {
        jQuery.ajax(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/aufgabenplugin/index"
                + "/remove_file/" + id + "?cid=" + seminar_id, {
            success : function() {
                jQuery('#uploaded_files tr[data-fileid=' + id + ']').remove();
            },
            error: function(xhr) {
                var json = jQuery.parseJSON(xhr.responseText);
                alert('Fehler - Server meldet: ' + json.message);
            }
        });
    },

    upload: function() {
        // do nothing if upload has been disabled
        if (jQuery('upload_button').hasClass('disabled')) {
            return;
        }

        // set upload as disabled
        jQuery('#upload_button').addClass('disabled');

        // upload each file separately to allow max filesize for each file
        _.each(STUDIP.epp.files, function (data) {
            if (data.files[0].size > 0 && data.files[0].size <= STUDIP.epp.maxFilesize) {
                data.submit();
            }
        });

        STUDIP.epp.files = {};
        STUDIP.epp.file_id = 0;
    },

    createQuestion: function(question, link) {
        var questionTemplateData = {
            question: question,
            confirm: link
        }

        jQuery('#epp').append(STUDIP.epp.questionTemplate(questionTemplateData));
    },

    closeQuestion: function() {
        jQuery('#epp .modaloverlay').remove();
    }
};
