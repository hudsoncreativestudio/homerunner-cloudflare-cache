(function ($, settings) {
  $(document.body).on("click", ".homecfcc-form-wrap>form>h2", function () {
    if ($(this).next("table.form-table").length > 0) {
      if ($(this).hasClass("open")) {
        $(this).removeClass("open").addClass("close");
        $(this).next("table.form-table").removeClass("open").addClass("close");
      } else {
        $(this).removeClass("close").addClass("open");
        $(this).next("table.form-table").removeClass("close").addClass("open");
      }
    }
    return false;
  });

  $(document).ready(function () {
    // if there's more than one section, use toggle.
    if ($(".homecfcc-form-wrap>form>h2").length > 1) {
      $(".homecfcc-form-wrap>form>h2")
        .addClass("close")
        .next("table.form-table")
        .addClass("close");
    } else if ($(".homecfcc-form-wrap>form>h2").length > 0) {
      $(".homecfcc-form-wrap>form>h2")
        .addClass("open")
        .next("table.form-table")
        .addClass("open");
    }
  });

  /**
   * Generic button to perform rest api requests.
   */
  $(document.body).on("click", ".homecfcc-rest-btn", function (e) {
    e.preventDefault();

    const $btn = $(this);
    const path = $(this).data("path");
    const method = $(this).data("method") || "get";
    const target = $(this).data("target") || "alert";
    const doReload = $(this).data("reload") ? true : false;

    // For delete action, perform an alert.
    if ("delete" === method && !confirm(settings.actionAlert)) {
      return false;
    }

    const setMessage = (message) => {
      if (target === "alert") {
        alert(message);
      } else if ($(target).length > 0) {
        $(target).text(r.message);
      }
    };

    $btn.prop("disabled", true);

    $.ajax({
      url: settings.restEndpoint + path,
      type: method,
      headers: { "X-WP-Nonce": settings.restNonce },
    })
      .done(function (r) {
        if (r.message) {
          setMessage(r.message);
        }
        if (doReload) {
          window.location.reload();
        }
      })
      .fail(function (xhr) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
          setMessage(xhr.responseJSON.message);
        } else if (
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
        ) {
          setMessage(xhr.responseJSON.data.message);
        } else {
          setMessage(settings.serverSideRrror);
        }
      })
      .always(function () {
        $btn.prop("disabled", false);
      });

    return false;
  });

  $(document.body).on("submit", ".homecfcc-rest-form", function (e) {
    e.preventDefault();

    const $button =
      $(this).find('[type="submit"]') || $(this).find('[type="button"]');
    const path = $(this).attr("action") || "";
    const method = $(this).attr("method") || "get";
    const target = $(this).data("target") || "alert";
    const doReload = $(this).data("reload") ? true : false;
    const data = $(this).serialize();

    // For delete action, perform an alert.
    if ("delete" === method && !confirm(settings.actionAlert)) {
      return false;
    }

    const setMessage = (message) => {
      if (target === "alert") {
        alert(message);
      } else if ($(target).length > 0) {
        $(target).text(r.message);
      }
    };

    $button.prop("disabled", true);

    $.ajax({
      url: settings.restEndpoint + path,
      type: method,
      headers: { "X-WP-Nonce": settings.restNonce },
      data: data,
    })
      .done(function (r) {
        if ("0" === r) {
          setMessage(settings.serverSideRrror);
        } else if (typeof r.message !== "undefined") {
          setMessage(r.message);
        }

        if (doReload) {
          window.location.reload();
        }
      })
      .fail(function (xhr) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
          setMessage(xhr.responseJSON.message);
        } else if (
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
        ) {
          setMessage(xhr.responseJSON.data.message);
        } else {
          setMessage(settings.serverSideRrror);
        }
      })
      .complete(function () {
        $button.prop("disabled", false);
      });

    return false;
  });

  $(document.body).on("submit", ".homecfcc-ajax-form", function (e) {
    e.preventDefault();

    var $form = $(this),
      $button = $form.find('[type="submit"]'),
      $target = $form.data("target") ? $($form.data("target")) : $button,
      method = $form.attr("method") || "POST";

    var data = $form.serialize();

    $button.prop("disabled", true);
    $target.html(settings.loading).show();

    $.ajax({
      method: method,
      url: ajaxurl,
      data: data,
    })
      .done(function (r) {
        if ("0" === r) {
          $target.html("Invalid form response.");
        } else if (typeof r.message !== "undefined") {
          if (r.success) {
            $target.html(r.message);
          } else {
            $target.html(r.message);
          }
        } else {
          $target.html(r.html);
        }
      })
      .fail(function (xhr, textStatus, errorThrown) {
        if (xhr.responseText && xhr.responseText === "0") {
          $target.html(settings.requestFailed);
        } else {
          $target.html(errorThrown);
        }
        $target.show();
      })
      .complete(function () {
        $button.prop("disabled", false);
      });

    return false;
  });

  $(document.body).on("click", ".homecfcc-ajax-btn", function () {
    const $button = $(this);
    const action = $(this).data("action") || "homecfcc_ajax";
    const $target = $(this).data("target")
      ? $($(this).data("target"))
      : $button;
    const doConfirm = $(this).data("confirm");
    const textNow = $button.text();
    let data = { action };

    if (doConfirm && !confirm(settings.actionAlert)) {
      return false;
    }

    $button.prop("disabled", true);
    $target.text(settings.loading).show();

    $.post(settings.ajaxUrl, data)
      .done(function (r) {
        if (r === "0") {
          $target.html(settings.requestFailed);
        } else if (r.message) {
          $target.html(r.message);
        }

        $target.show();

        $(document.body).trigger(action + "/done", [r, $button, $target]);
      })
      .fail(function (xhr, textStatus, errorThrown) {
        if (xhr.responseText && xhr.responseText === "0") {
          $target.html(settings.requestFailed);
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          $target.html(xhr.responseJSON.message);
        } else {
          $target.html(errorThrown);
        }
        $target.show();
      })
      .always(function () {
        if ($button === $target) {
          setTimeout(function () {
            $button.text(textNow);
            $button.prop("disabled", false);
          }, 3000);
        } else {
          $button.prop("disabled", false);
        }
      });
  });
})(jQuery, homecfcc_admin_common_settings);
