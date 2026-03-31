/*--------------
Variables
--------------*/

let p_uploader;
let file_upload = [];
let last_nb_files = 0;

const p_ai_infos = $('#p_ai_infos');
const p_ai_upload = $('#togglePwgAiMode');
const p_ai_upload_caption = $('#pAiUploadCaption');
const p_ai_upload_tagging = $('#pAiUploadTagging');
const p_ai_upload_ocr = $('#pAiUploadOCR');

/*--------------
On DOM load
--------------*/
$(function() {
  $('#p_ai_options')
    .appendTo('#uploadOptionsContent')
    .removeClass('hidden')
    .addClass('p-ai-options');

  p_ai_infos
    .insertAfter('#startUpload')
    .css('left', $('#startUpload').innerWidth() + 45);

  $('#togglePwgAiMode').on('change', function() {
    if ($(this).is(':checked')) {
      $('#p_ai_options_content').removeClass('hidden!');
      reset_p_ai_infos(last_nb_files);
    } else {
      $('#p_ai_options_content').addClass('hidden!');
      p_ai_infos.fadeOut();
    }
  });

  $("#uploadOptions").trigger('click');

  p_uploader = $('#uploader').pluploadQueue();
  if (p_uploader) {
    // Todo: update uploader to add icon-robot-head
    // p_uploader.bind('FilesAdded', function(up, files) {
    //   setTimeout(function() {
    //     files.forEach(function(file) {
    //       $('#' + file.id + ' .plupload_clearer').before(
    //         '<div class="p-ai-file-info text-right"><i class="icon-robot-head"></i></div>'
    //       );
    //     });
    //   }, 0);
    // });

    p_uploader.bind('PostInit', function(up) {
      up.bind('BeforeUpload', function(up, file) {
        const params = up.getOption('multipart_params') || {};
        params.ai = p_ai_upload.is(':checked');
        if (params.ai) {
          params.caption = p_ai_upload_caption.is(':checked');
          params.tagging = p_ai_upload_tagging.is(':checked');
          params.ocr = p_ai_upload_ocr.is(':checked');
        }
        up.setOption('multipart_params', params);
      });

      up.bind('FilesAdded', function(up, files) {
        last_nb_files = p_uploader.files.length ?? 0;
        reset_p_ai_infos(p_uploader.files.length ?? 0);
      });

      up.bind('FilesRemoved', function(up, files) {
        last_nb_files = p_uploader.files.length ?? 0;
        reset_p_ai_infos(p_uploader.files.length ?? 0);
      });

      up.bind('StateChanged', function(up) {
        if (up.state === plupload.STARTED) {
          p_ai_infos.hide();
        }
      });
    });
  }
});

function reset_p_ai_infos(nb_files) {
  if (!is_ai_checked()) {
    return;
  }

  if (nb_files > 0) {
    p_ai_infos.fadeIn();
  } else {
    p_ai_infos.fadeOut();
  }
  const text = sprintf(str_p_ai_infos_text, nb_files);
  $('#p_ai_infos_text').text(text);
}

function is_ai_checked() {
  return p_ai_upload.is(':checked')
  && (p_ai_upload_caption.is(':checked') 
    || p_ai_upload_tagging.is(':checked') 
    || p_ai_upload_ocr.is(':checked'));
}