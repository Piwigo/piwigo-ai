/*--------------
Variables
--------------*/

let p_uploader;
let file_upload = [];
let last_nb_files = 0;
let p_ai_upload_errors = {};
let p_ai_upload_unsent = 0;

const p_ai_infos = $('#p_ai_infos');
const p_ai_upload = $('#togglePwgAiMode');
const p_ai_upload_caption = $('#pAiUploadCaption');
const p_ai_upload_tagging = $('#pAiUploadTagging');
const p_ai_upload_ocr = $('#pAiUploadOCR');
const p_ai_upload_embedding = $('#pAiUploadEmbedding');

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

  const update_tags_warning = function() {
    $('#p_ai_tags_warning').toggle(p_ai_upload.is(':checked') && p_ai_upload_tagging.is(':checked'));
  };
  update_tags_warning();
  $('#togglePwgAiMode, #pAiUploadTagging').on('change', update_tags_warning);

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
      // negative priority so we run AFTER photos_add_direct.js BeforeUpload
      up.bind('BeforeUpload', function(up, file) {
        const params = up.getOption('multipart_params') || {};
        params.ai = p_ai_upload.is(':checked');
        if (params.ai) {
          params.caption = p_ai_upload_caption.is(':checked');
          params.tagging = p_ai_upload_tagging.is(':checked');
          params.ocr = p_ai_upload_ocr.is(':checked');
          params.embedding = p_ai_upload_embedding.is(':checked');
        }
        up.setOption('multipart_params', params);
      }, -1);

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
          p_ai_upload_errors = {};
          p_ai_upload_unsent = 0;
        }
      });

      up.bind('FileUploaded', function(up, file, info) {
        const error = p_ai_response_header(info.responseHeaders, 'X-Piwigo-AI-Error');
        if (error !== null) {
          p_ai_upload_errors[error] = p_ai_upload_errors[error] || [];
          p_ai_upload_errors[error].push(file.name);
        }
        if (p_ai_response_header(info.responseHeaders, 'X-Piwigo-AI-Unsent') !== null) {
          p_ai_upload_unsent++;
        }
      });

      up.bind('UploadComplete', function() {
        p_ai_show_upload_report();
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

function p_ai_response_header(headers, name) {
  const line = (headers || '').split(/\r?\n/).find(function(line) {
    return line.toLowerCase().indexOf(name.toLowerCase() + ':') === 0;
  });
  if (!line) {
    return null;
  }
  return decodeURIComponent(line.slice(name.length + 1).trim());
}

function p_ai_show_upload_report() {
  $.each(p_ai_upload_errors, function(message, files) {
    const text = files.length === 1
      ? sprintf(str_p_ai_error_one, files[0], message)
      : sprintf(str_p_ai_error_many, files.length, message);
    $('.errors ul').append($('<li>').text(text));
    $('.errors').show();
  });

  if (p_ai_upload_unsent > 0) {
    const text = sprintf(p_ai_upload_unsent === 1 ? str_p_ai_unsent_one : str_p_ai_unsent_many, p_ai_upload_unsent);
    if (!$('.infos ul').length) {
      $('.infos').append('<ul></ul>');
    }
    $('.infos ul').last().append($('<li>').text(text).prepend('<i class="eiw-icon icon-clock"></i>'));
    $('.infos').show();
  }
}

function is_ai_checked() {
  return p_ai_upload.is(':checked')
  && (p_ai_upload_caption.is(':checked') 
    || p_ai_upload_tagging.is(':checked')
    || p_ai_upload_ocr.is(':checked')
    || p_ai_upload_embedding.is(':checked'));
}