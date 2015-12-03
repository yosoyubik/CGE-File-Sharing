"use strict";
angular.module("batchUploadApp", ["ngCookies", "ngResource", "ngSanitize", "ngRoute", "angularFileUpload", "batchUploadApp.config", "ui.bootstrap", "ui-notification"])
    .config(["$routeProvider", function (a) {
        a.when("/", {
                templateUrl: "views/main.html",
                controller: "MainCtrl"
            })
            .otherwise({
                redirectTo: "/"
            })
    }]), angular.module("batchUploadApp")
    .controller("MainCtrl", ["$scope", "FileUploader", "PostMessageService", "ENV", "UIDservice", "FastaValidation", "Notification", function (a, b, c, d, e, f, g) {
        a.isCollapsed = !0, a.infoMessage = "", a.errorsExist = !1, a.multipleErrors = !1, a.ringTrial = !1, a.$watch("ringTrial", function (b, c) {
            b != c && (a.uploader.formData[0].ringTrial = b)
        }), a.excelURL = d.apiEndpoint + "metadataform.xlsx", a.excelInfoText = 'Download the <a href="' + d.apiEndpoint + 'metadataform.xlsx">template here<a/> and fill it out with information about your isolates: Date, Country...', a.missingFiles = [], a.chunksFailed = [], a.downloadExcelFile = function () {
            console.log(a.excelURL);
            var b = angular.element("#excelLink");
            b.attr({
                href: a.excelURL,
                target: "_self",
                download: "metadataform.xlsx"
            })[0].click()
        }, console.log(d);
        var h = a.uploader = new b({
            url: d.apiEndpoint + "blobs.php",
            formData: [{
                data: JSON.stringify({
                    upload_dir: 0
                }),
                UID: e.updateUID(),
                chunkID: 0,
                nChunks: 0,
                isChunk: !1,
                fileName: "",
                errorChunk: !1,
                needsMerge: !1,
                merge: d.apiEndpoint + "blobsMerge.php",
            }],
            filters: [{
                name: "allFilesExist",
                fn: function (b) {
                    return -1 !== a.files.indexOf(b.name) || b.isChunk ? !0 : (console.log("filter failed"), g.warning({
                        message: b.name + " could not be found in the excel template",
                        delay: 5e3,
                        positionY: "top",
                        positionX: "right"
                    }), !1)
                }
            }, {
                name: "duplicateFiles",
                fn: function (b) {
                    var c = [];
                    return a.uploader.getNotUploadedItems()
                        .forEach(function (a) {
                            c.push(a.file.name)
                        }), 0 !== c.length && -1 !== c.indexOf(b.name) ? (g.warning({
                            message: b.name + " is already added to the queue",
                            delay: 5e3,
                            positionY: "top",
                            positionX: "right"
                        }), !1) : !0
                }
            }]
        });
        h.onAfterAddingFile = function (b) {
            b.formData[0].data = JSON.stringify(a.metadata[b.file.name])
        }, h.onWhenAddingFileFailed = function (b, c, d) {
            a.excelRight || (a.infoMessage = " Excel Sheet should be loaded first", a.errorsExist = !0, a.multipleErrors = !1, a.messages = [])
        }, h.onBeforeUploadItem = function (b) {
            var c = a.uploader.queue;
            c.length !== a.files.length ? (console.log("cancelling..."), b.cancel(), a.infoMessage = " Some files are missing", a.errorsExist = !0, a.multipleErrors = !1, a.messages = []) : a.errorsExist = !1
        }, h.onCompleteItem = function (b, d, e, f) {
            "Success" === d.state ? (console.info(d), c.messages(d.response)) : "Error" === d.state ? (console.error(d), a.infoMessage = " Error uploading the following files", a.errorsExist = !0, a.multipleErrors = !0, a.messages.push(d.response)) : (console.log("NO SUCCESS NO ERROR", d), console.log("Asume chunks this was one of the chunks that failed"), a.chunksFailed.push(d.chunkID), a.infoMessage = " Error uploading to the server", a.errorsExist = !0, a.multipleErrors = !0, a.messages.push(b.fileName))
        }, h.onCompleteAll = function () {
            a.errorsExist ? console.log("Errors uploading some files") : (console.info("Complete All"), c.outgoing(), a.uploader.formData[0].UID = e.updateUID())
        }, h.onErrorItem = function (b, c, d, e) {
            console.log(e), a.infoMessage = " Server is not responding when uploading files", a.errorsExist = !0, a.multipleErrors = !1, a.messages = []
        }, h.onCompleteChunk = function (b, c, d, e) {
            "Error" === c.state && "Moving chunk" === c.response && a.chunksFailed.push(c.chunkID)
        }, h.onErrorChunk = function (b, c, d, e) {
            console.log("onErrorChunk", c), a.infoMessage = " Error creating file on the server. We will attempt to resend parts of the file", a.errorsExist = !0, a.multipleErrors = !1, a.messages = [], console.log("SOME CHUNKS THAT FAILED", a.chunksFailed), a.errorsExist = !1, a.chunksFailed = b.blobsFailed
        }, a.$on("uploadAllFromIframe", function () {
            h.queue.length !== a.files.length ? console.log("Files missing on the queue") : h.uploadAll()
        }), a.checkAndSubmitFiles = function () {
            if (h.queue.length !== a.files.length) {
                var b = a.files.slice();
                h.getNotUploadedItems()
                    .forEach(function (a) {
                        -1 !== b.indexOf(a.file.name) && delete b[b.indexOf(a.file.name)]
                    }), console.log("Files missing on the queue"), a.infoMessage = " Some of the file(s) specified in the excel template could not be found", a.messages = b, a.errorsExist = !0, a.multipleErrors = !0
            } else h.uploadAll()
        }
    }]), angular.module("batchUploadApp")
    .directive("ngPostMessage", ["$window", "PostMessageService", function (a, b) {
        return {
            restrict: "A",
            controller: ["$scope", "$attrs", "PostMessageService", function (a, b, c) {
                a.$on("outgoingMessage", function (b) {
                    if (console.log(b), a.sender) {
                        var d = JSON.stringify({
                            status: 200,
                            message: c.messages()
                        });
                        a.sender.postMessage(d, "*")
                    }
                })
            }],
            link: function (b, c, d) {
                b.sendMessageToService = function (a) {
                        if (a && a.originalEvent.data) {
                            var c = null;
                            b.sender = a.originalEvent.source;
                            try {
                                c = angular.fromJson(a.originalEvent.data)
                            } catch (d) {
                                c = {
                                    message: a.originalEvent.data
                                }
                            }
                            "connect" === c.message ? console.log("Succesfully connected") : "upload" === c.message ? (console.log("Manually uploading"), b.$broadcast("uploadAllFromIframe")) : console.log("Post message undefined", c.message)
                        }
                    }, console.log("binding message"), angular.element(a)
                    .bind("message", b.sendMessageToService)
            }
        }
    }]), angular.module("batchUploadApp")
    .factory("PostMessageService", ["$rootScope", function (a) {
        var b = [],
            c = {
                messages: function (c) {
                    return c && (b.push(c), a.$apply()), b
                },
                outgoing: function () {
                    a.$broadcast("outgoingMessage")
                }
            };
        return c
    }]), angular.module("batchUploadApp")
    .directive("file", ["ExcelParserService", "UIDservice", function (a, b) {
        return {
            templateUrl: "templates/uploadExcelFileTemplate.html",
            restrict: "A",
            link: function (c, d, e) {
                console.log("Init file directive..."), console.log(c), c.excelRight = !1;
                var f = a.parseFile,
                    g = !1;
                angular.element(document.querySelector("#fileInput"))
                    .bind("change", function (a) {
                        if (!g) {
                            c.errorsExist = !1, c.isolates = [], c.loadData = !1;
                            var b = a.target.files,
                                d = b[0];
                            console.log(d), c.testloaded = !1, console.log("Parsing excel...")
                        }
                        c.excelRight || f(d, !0, c.ringTrial)
                            .then(function (a) {
                                if (a.errors.nErrors > 0) {
                                    c.errorsExist = !0, c.multipleErrors = !0, c.messages = a.errors.messages, c.infoMessage = " " + a.errors.nErrors + " errors were detected in the Excel file: " + d.name, g = !0;
                                    var b = angular.element(document.querySelector("#fileInput"));
                                    b.replaceWith(b.val("")
                                        .clone(!0)), g = !1
                                } else c.excelRight = !0, c.errorsExist = !1, angular.extend(c, a)
                            })
                    }), c.removeExcelSheet = function () {
                        if (c.excelRight) {
                            var a = angular.element(document.querySelector("#fileInput"));
                            a.replaceWith(a.val("")
                                .clone(!0)), c.excelRight = !1, c.excelExists = !1, c.errorsExist = !1, c.uploader.clearQueue(), c.uploader.formData[0].UID = b.updateUID()
                        }
                    }
            }
        }
    }]), angular.module("batchUploadApp")
    .service("ExcelParserService", ["$q", "ExcelvalidationService", "GeoLocationService", function (a, b, c) {
        this.parseFile = function (c, d, e) {
            console.log("Parsing file...");
            var f = a.defer(),
                g = f.promise,
                h = new FileReader,
                i = {},
                j = [],
                k = {
                    messages: [],
                    nErrors: 0
                };
            return h.onload = function (a) {
                var c = new JSZip,
                    g = c.load(a.target.result, {
                        base64: !1
                    }),
                    h = XLSX.parseZip(g),
                    l = h.SheetNames,
                    m = {};
                l.forEach(function (a) {
                    if ("Metadata" === a) {
                        var c = h.Sheets[a],
                            f = XLSX.utils.sheet_to_row_object_array(c);
                        f.length > 0 && (m[a] = f, f.forEach(function (a, c) {
                            var f = {
                                    sample_name: "",
                                    group_name: "",
                                    file_names: "",
                                    sequencing_platform: "",
                                    sequencing_type: "",
                                    pre_assembled: "",
                                    sample_type: "isolate",
                                    organism: "unknown",
                                    strain: "",
                                    subtype: "",
                                    country: "",
                                    region: "",
                                    city: "",
                                    zip_code: "",
                                    longitude: "",
                                    latitude: "",
                                    location_note: "",
                                    isolation_source: "",
                                    source_note: "",
                                    pathogenic: "unknown",
                                    pathogenicity_note: "",
                                    collection_date: "",
                                    collected_by: "",
                                    usage_restrictions: "private",
                                    release_date: "",
                                    email_address: "",
                                    notes: ""
                                },
                                g = ["sample_name", "group_name", "file_names", "sequencing_platform", "sequencing_type", "pre_assembled", "sample_type", "organism", "strain", "subtype", "country", "region", "city", "zip_code", "longitude", "latitude", "location_note", "isolation_source", "source_note", "pathogenic", "pathogenicity_note", "collection_date", "collected_by", "usage_restrictions", "release_date", "email_address", "notes"];
                            if (angular.extend(f, a), e || Object.keys(f)
                                .forEach(function (a) {
                                    -1 === g.indexOf(a) && delete f[a]
                                }), console.log(f), b.emptyRow(f)) console.log("Row empty", a);
                            else {
                                f.upload_dir = c + 1, f.batch = !0, ("" !== f.latitude || "" !== f.longitude) && (f.location_uncertainty_flag = 0);
                                var h = b.isolate(f, c + 2, d, j);
                                if (h.errors > 0 || k.nErrors > 0) 0 !== k.nErrors ? k.messages = k.messages.concat(h.message) : k.messages = h.message, k.nErrors += h.errors;
                                else {
                                    var l = f.file_names.split(" ");
                                    l.forEach(function (a) {
                                        i[a] = f, j.push(a)
                                    })
                                }
                            }
                        }))
                    }
                }), 0 === j.length && 0 === k.nErrors && (k.nErrors += 1, k.messages.push("The excel template is empty")), f.resolve({
                    metadata: i,
                    files: j,
                    errors: k
                })
            }, h.readAsArrayBuffer(c), g
        }
    }]), angular.module("batchUploadApp")
    .service("ExcelvalidationService", ["$filter", function (a) {
        this.emptyRow = function (a) {
            var b = ["sample_name", "group_name", "file_names", "sequencing_platform", "sequencing_type", "pre_assembled", "sample_type", "organism", "strain", "subtype", "country", "region", "city", "zip_code", "longitude", "latitude", "location_note", "isolation_source", "source_note", "pathogenic", "pathogenicity_note", "collection_date", "collected_by", "usage_restrictions", "release_date", "email_address", "notes"],
                c = 0;
            return b.forEach(function (b) {
                "upload_dir" !== b && ("release_date" !== b && a[b] || 0 === a[b]) && (c += 1)
            }), b.length === c
        }, this.isolate = function (a, b, c, d) {
            var e = ["LS454", "Illumina", "Ion Torrent", "ABI SOLiD", "unknown"],
                f = ["single", "paired", "mate-paired", "unknown"],
                g = ["yes", "no"],
                h = ["human", "water", "food", "animal", "other", "laboratory", "unknown"],
                i = ["yes", "no", "unknown"],
                j = d3.time.format("%Y-%m-%d"),
                k = d3.time.format("%Y-%m"),
                l = d3.time.format("%Y"),
                m = "",
                n = [],
                o = 0;
            c && "" === a.file_names && (m = "[Line " + b.toString() + "] Isolate files missing", n.push(m), o += 1);
            var p = a.file_names.split(" "),
                q = angular.copy(d),
                r = !0;
            p.forEach(function (a) {
                -1 !== q.indexOf(a) ? "" !== a ? (m = "[Line " + b.toString() + "] File (" + a + ") already included", n.push(m), o += 1) : r && (m = "[Line " + b.toString() + "] White spaces in file_names field", n.push(m), o += 1, r = !1) : q.push(a)
            }), -1 === e.indexOf(a.sequencing_platform) && ("" !== a.sequencing_platform.trim() ? (m = "[Line " + b.toString() + '] Sequencing Platform "' + a.sequencing_platform + '" is not a valid option', n.push(m), o += 1) : "no" === a.pre_assembled ? (m = "[Line " + b.toString() + "] Sequencing Platform missing", n.push(m), o += 1) : a.sequencing_platform = "unknown"), -1 === f.indexOf(a.sequencing_type) && ("" !== a.sequencing_type.trim() ? (m = "[Line " + b.toString() + '] Sequencing Type "' + a.sequencing_type + '" is not a valid option', n.push(m), o += 1) : "no" === a.pre_assembled ? (m = "[Line " + b.toString() + "] Sequencing Type missing", n.push(m), o += 1) : a.sequencing_type = "unknown"), "yes" === a.pre_assembled ? (console.log(p.length), 1 !== p.length && (m = "[Line " + b.toString() + '] when preAssembled "' + a.pre_assembled + '" a single file is expected', n.push(m), o += 1)) : "single" === a.sequencing_type ? "Illumina" === a.sequencing_platform || "LS454" === a.sequencing_platform ? 1 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" a single file is expected', n.push(m), o += 1) : "ABI SOLiD" === a.sequencing_platform ? 2 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" two files are expected', n.push(m), o += 1) : "Ion Torrent" === a.sequencing_platform ? 1 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '"one file is expected', n.push(m), o += 1) : (m = "[Line " + b.toString() + '] a format with Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '"is not supported by our assembler', n.push(m), o += 1) : "paired" === a.sequencing_type ? "Illumina" === a.sequencing_platform ? 2 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" two files are expected', n.push(m), o += 1) : "ABI SOLiD" === a.sequencing_platform ? 4 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" four files are expected', n.push(m), o += 1) : "LS454" === a.sequencing_platform ? 1 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" a single file is expected', n.push(m), o += 1) : (m = "[Line " + b.toString() + '] a format with Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '"is not supported by our assembler', n.push(m), o += 1) : "mate-paired" === a.sequencing_type && ("ABI SOLiD" === a.sequencing_platform ? 4 !== p.length && (m = "[Line " + b.toString() + '] When Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '" two files are expected', n.push(m), o += 1) : (m = "[Line " + b.toString() + '] a format with Sequencing Type "' + a.sequencing_type + '" and sequencingPlatform "' + a.sequencing_platform + '"is not supported by our assembler', n.push(m), o += 1)), -1 === g.indexOf(a.pre_assembled) && (m = "" !== a.pre_assembled.trim() ? "[Line " + b.toString() + '] Pre Assembled "' + a.pre_assembled + '" is not a valid option' : "[Line " + b.toString() + "] Pre Assembled missing", n.push(m), o += 1), "" === a.country.trim() && (m = "[Line " + b.toString() + "] Country not present", n.push(m), o += 1), -1 === h.indexOf(a.isolation_source) && (m = "" !== a.isolation_source.trim() ? "[Line " + b.toString() + '] Isolation Source "' + a.isolation_source + '" is not a valid option' : "[Line " + b.toString() + "] Isolation Source missing", n.push(m), o += 1), -1 === i.indexOf(a.pathogenic) && "" !== a.pathogenic.trim() && (m = "[Line " + b.toString() + '] Pathogenic "' + a.pathogenic + '" is not a valid option', n.push(m), o += 1);
            var s = j.parse(a.collection_date),
                t = k.parse(a.collection_date),
                u = l.parse(a.collection_date);
            null != s && j(s) === a.collection_date || null != t && k(t) === a.collection_date || null != u && l(u) === a.collection_date || (m = "[Line " + b.toString() + "] Invalid format for collection date", n.push(m), o += 1);
            var s = j.parse(a.release_date);
            return null === s && "" !== a.release_date.trim() && (m = "[Line " + b.toString() + "] Invalid format for release_date date", n.push(m), o += 1), 0 === o ? {
                message: "",
                errors: o
            } : {
                message: n,
                errors: o
            }
        }
    }]), angular.module("batchUploadApp")
    .service("GeoLocationService", ["$q", function (a) {
        var b = GeocoderJS.createGeocoder("openstreetmap");
        this.getLatLngFromLocation = function (c, d) {
            var e = a.defer(),
                f = e.promise,
                g = "";
            return "" === c.longitude || "" === c.latitude ? (g = "" !== c.region ? c.city + ", " + c.region + ", " + c.country : c.city + ", " + c.country, console.log("Asking for address...", g), b.geocode(g, function (a) {
                console.log("Coordinates resolved...", g, a), a.length > 0 ? (c.longitude = a[0].longitude, c.latitude = a[0].latitude) : (d.messages.push("Unknown location in isolate number: " + c.sample_name), d.nErrors += 1), e.resolve(c)
            })) : (console.log("we shouldnt see this"), e.resolve(c)), f
        }, this.getLocationFromLatLng = function (c) {
            var d = a.defer(),
                e = d.promise;
            return b.geodecode(c.latitude, c.longitude, function (a) {
                console.log(a), d.resolve(c)
            }), e
        }
    }]), angular.module("batchUploadApp")
    .service("UIDservice", function () {
        var a = function () {
            var a = new Date,
                b = a.getDay() + 1,
                c = a.getDate(),
                d = a.getFullYear(),
                e = a.getMonth() + 1,
                f = a.getHours(),
                g = a.getMinutes(),
                h = a.getMilliseconds(),
                i = Math.floor(1e6 * Math.random()) + 1,
                j = b + "_" + c + "_" + e + "_" + d + "_" + f + g + "_" + h + "_",
                k = j + i;
            return k
        };
        this.UID = a(), this.updateUID = function () {
            return a()
        }
    }), angular.module("batchUploadApp")
    .service("FastaValidation", ["$q", function (a) {
        this.call = function (b, c) {
            var d = a.defer(),
                e = d.promise,
                f = new FileReader;
            console.log(f), f.onload = function (a) {
                if (console.log(a), a.target.readyState === FileReader.DONE) {
                    var b = ">" === a.target.result,
                        e = {};
                    b && "yes" === c ? (console.log("File type right..."), e.correct = !0) : b || "yes" !== c ? b && "no" === c ? (e.correct = !1, e.message = "FASTA file provided and pre_assembled option set to NO") : e.correct = !0 : (e.correct = !1, e.message = "FASTQ file provided and pre_assembled option set to YES"), console.log(e.message), d.resolve(e)
                }
            };
            var g = b.slice(0, 1);
            return f.readAsText(g), e
        }
    }]);
