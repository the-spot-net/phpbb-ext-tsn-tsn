/**
 * AJAX Request handling for tsn, with code available for a Promise to resolve after the response handling
 */
import tsnCommon from './tsnCommon';

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
        } else {
          contentType = 'application/json';
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
            successCallback(response);
          }

          if (typeof resolve === 'function') {
            resolve(response);
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
}

// Response Status Codes
tsnRequest.constants = {
  ERROR: 0,
  SUCCESS: 1,
  INFO_WARN: 2
};
