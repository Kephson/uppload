import '../Stylesheet/style.css';
import {de_master} from "../I18n/de_master.ts";

import {
  Uppload,
  Local,
  Crop,
  Rotate,
  xhrUploader,
} from "uppload";

const upploadElements = document.getElementsByClassName('powermail_uppload');

Array.prototype.forEach.call(upploadElements, function (el) {

  let pic = el.dataset.pic;
  let btn = el.dataset.btn;
  let input = el.dataset.input;
  let maxwidth = el.dataset.maxwidth;
  let maxheight = el.dataset.maxheight;
  let maxFileSize = el.dataset.maxFileSize;

  let uploader = new Uppload({
    lang: de_master,
    uploader: xhrUploader({
      endpoint: "/?uppload=1",
      fileKeyName: "file"
    }),
    bind: [
      document.querySelector(pic),
      document.querySelector(input)
    ],
    call: [
      document.querySelector(btn),
      document.querySelector(pic)
    ],
    maxWidth: maxwidth,
    maxHeight: maxheight
  });
  uploader.use([new Local({
    maxFileSize: maxFileSize,
    mimeTypes: ["image/png", "image/jpeg"]
  })]);
  uploader.use([
    new Crop(),
    new Rotate()
  ]);

});

