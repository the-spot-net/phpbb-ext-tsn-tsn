import tsnCommon from './tsnCommon';

/**
 * AJAX Request handling for tsn, with code available for a Promise to resolve after the response handling
 */
export default class tsnRequest {
  /**
   * Generic AJAX Request that executes actions when the promise is resolved
   *
   * @param endpoint
   * @param dataPacket
   * @param successCallback
   * @param errorCallback
   * @param resolveCallback
   */
  static ajaxRequest({
    endpoint,
    dataPacket = {},
    successCallback = () => {},
    errorCallback = undefined,
    resolveCallback = undefined
  } = {}) {
    const thisPromise = new Promise((resolve) => {
      // Leave as undefined
      let contentType;
      let processData;

      // Adjust the request based on the dataPacket object type
      if (typeof dataPacket === 'object') {
        if (dataPacket instanceof FormData) {
          // Prevent jQuery from processing the data if this is a FormData object
          // and remove contentType assumptions
          contentType = false;
          processData = false;
        }
      }

      $.ajax(endpoint, {
        method: 'POST',
        data: dataPacket,
        dataType: 'json', // Expecting a JSON packet
        contentType,
        processData,
        beforeSend() {
          $(document).trigger(tsnCommon.events.ajaxBefore);
        },
        success: (response) => {
          if (typeof successCallback === 'function') {
            successCallback({ response });
          }

          if (typeof resolve === 'function') {
            resolve({ response });
          }
        },
        error: errorCallback,
        complete() {
          $(document).trigger(tsnCommon.events.ajaxComplete);
        }
      });
    });

    thisPromise.then((response) => {
      if (typeof resolveCallback === 'function') {
        resolveCallback({ response });
      }
    });
  }

  /**
   * Redirect the browser accordingly, or open a new tab with the url
   * @param url
   * @param target
   */
  static redirect({
    url,
    target = null
  } = {}) {
    if (target.length) {
      window.open(url, target);
    } else {
      window.location.href = url;
    }
  }
}

// Response Status Codes
tsnRequest.constants = {
  ERROR: 0,
  SUCCESS: 1,
  INFO_WARN: 2
};
