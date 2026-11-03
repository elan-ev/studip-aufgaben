(function ($) {
    'use strict';

    // Keep each participant's take while Stud.IP destroys and reloads the dialog.
    const drafts = new Map();

    function initialize(form) {
        if ($(form).data('audio-feedback-initialized')) {
            return;
        }
        $(form).data('audio-feedback-initialized', true);

        const dialog = $(form).closest('.ui-dialog-content');
        const start = form.querySelector('[data-audio-start]');
        const stop = form.querySelector('[data-audio-stop]');
        const reset = form.querySelector('[data-audio-reset]');
        const upload = form.querySelector('[data-audio-upload]');
        const close = form.querySelector('[data-audio-close]');
        const preview = form.querySelector('[data-audio-preview]');
        const status = form.querySelector('[data-audio-status]');
        const error = form.querySelector('[data-audio-error]');
        let recorder = null;
        let stream = null;
        let blob = null;
        let previewUrl = null;
        let generation = 0;
        let uploading = false;
        let closed = false;

        function showPreview(recording) {
            blob = recording;
            previewUrl = URL.createObjectURL(blob);
            preview.hidden = false;
            preview.src = previewUrl;
            preview.load();
            start.disabled = false;
            reset.disabled = false;
            upload.disabled = false;
            status.textContent = form.dataset.ready;
        }

        function showError(message) {
            error.textContent = message;
            error.hidden = !message;
        }

        function releaseMicrophone() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        function clear(discard = true) {
            generation += 1;
            if (discard) {
                drafts.delete(form.action);
            }
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
            }
            recorder = null;
            releaseMicrophone();
            preview.pause();
            preview.removeAttribute('src');
            preview.load();
            preview.hidden = true;
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            blob = null;
            start.disabled = false;
            stop.disabled = true;
            reset.disabled = true;
            upload.disabled = true;
            status.textContent = '';
            showError('');
        }

        clear(false);
        const draft = drafts.get(form.action);
        if (draft) {
            const take = generation;
            start.disabled = true;
            draft.then(recording => {
                if (closed || take !== generation) {
                    return;
                }
                start.disabled = false;
                if (recording) {
                    showPreview(recording);
                }
            });
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
            start.disabled = true;
            showError(form.dataset.unsupported);
        }

        start.addEventListener('click', async function () {
            clear();
            start.disabled = true;
            const take = generation;
            try {
                const microphone = await navigator.mediaDevices.getUserMedia({ audio: true });
                // Permission may be granted after the dialog has already been closed.
                if (closed || take !== generation || !form.isConnected) {
                    microphone.getTracks().forEach(track => track.stop());
                    return;
                }
                stream = microphone;
                const mimeType = ['audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4']
                    .find(type => MediaRecorder.isTypeSupported(type));
                recorder = new MediaRecorder(stream, mimeType ? { mimeType: mimeType } : {});
                let finishDraft;
                drafts.set(form.action, new Promise(resolve => { finishDraft = resolve; }));
                const chunks = [];
                const currentRecorder = recorder;
                recorder.addEventListener('dataavailable', event => {
                    if (event.data.size) {
                        chunks.push(event.data);
                    }
                });
                recorder.addEventListener('error', function () {
                    finishDraft(null);
                    if (take === generation) {
                        clear();
                        showError(form.dataset.recordingError);
                    }
                });
                recorder.addEventListener('stop', function () {
                    if (take !== generation) {
                        finishDraft(null);
                        return;
                    }
                    releaseMicrophone();
                    recorder = null;
                    stop.disabled = true;
                    const recording = new Blob(chunks, { type: currentRecorder.mimeType || (chunks[0] && chunks[0].type) });
                    if (!recording.size) {
                        finishDraft(null);
                        clear();
                        showError(form.dataset.recordingError);
                        return;
                    }
                    blob = recording;
                    finishDraft(recording);
                    if (!closed) {
                        showPreview(blob);
                    }
                });
                recorder.start();
                stop.disabled = false;
                status.textContent = form.dataset.recording;
            } catch (exception) {
                if (!closed && take === generation) {
                    clear();
                    showError(form.dataset.microphoneError);
                }
            }
        });

        stop.addEventListener('click', function () {
            stop.disabled = true;
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
            }
        });
        reset.addEventListener('click', clear);
        close.addEventListener('click', function () { STUDIP.Dialog.close(); });
        dialog.on('dialogbeforeclose.audioFeedback', function (event) {
            if (uploading) {
                event.preventDefault();
            }
        });
        dialog.one('dialogclose.audioFeedback', function () {
            closed = true;
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
            }
            releaseMicrophone();
            preview.pause();
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            dialog.off('.audioFeedback');
            window.removeEventListener('pagehide', clear);
        });
        window.addEventListener('pagehide', clear);

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!blob || uploading) {
                return;
            }
            const extension = blob.type.includes('mp4') ? 'm4a' : blob.type.includes('ogg') ? 'ogg' : 'webm';
            const file = new File([blob], 'Audio-Feedback-' + Date.now() + '.' + extension, { type: blob.type });
            if (!STUDIP.Files.validateUpload(file)) {
                showError(form.dataset.uploadError);
                return;
            }
            const data = new FormData(form);
            data.append('file[]', file, file.name);
            uploading = true;
            preview.pause();
            [start, stop, reset, upload, close].forEach(button => { button.disabled = true; });
            showError('');
            status.textContent = form.dataset.uploading;
            $.ajax({
                url: form.action,
                type: 'POST',
                data: data,
                contentType: false,
                processData: false,
                dataType: 'json'
            }).done(function (result) {
                if (result.success) {
                    drafts.delete(form.action);
                    window.location.assign(form.dataset.returnUrl);
                } else {
                    showError(result.error || form.dataset.uploadError);
                }
            }).fail(function () {
                showError(form.dataset.uploadError);
            }).always(function () {
                uploading = false;
                [start, reset, upload, close].forEach(button => { button.disabled = false; });
                status.textContent = form.dataset.ready;
            });
        });
    }

    function initializeForms() {
        document.querySelectorAll('[data-audio-feedback-form]').forEach(initialize);
    }

    $(initializeForms);
    $(document).on('dialog-update', initializeForms);
})(jQuery);
