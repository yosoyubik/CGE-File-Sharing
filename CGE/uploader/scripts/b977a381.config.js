"use strict";

 var configapp = angular.module("batchUploadApp.config", [])

.constant("ENV", {
  "status": "production",
  "apiEndpoint": "tools/server/uploader/"
})

;
