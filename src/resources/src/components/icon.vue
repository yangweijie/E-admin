<template>
    <el-popover
            v-model:visible="visible"
            width="910"
            trigger="click"
            >
        <template #reference>
            <el-input v-model="value" clearable @input="filterIcons" @clear="filterIcons"><template #append><span @click="visible=true" style="cursor: pointer"><i :class="value" v-if="value"></i><template v-else><i class="fa fa-hand-pointer-o"></i>请选择</template></span></template></el-input>
        </template>
        <el-scrollbar>
        <ul class="iconCollection">
            <li v-for="icon in iconList" @click="selectIcon(icon)">
              <i :class="icon" @mouseover="e=>popper(e,icon)" @mouseout="hide"></i>
            </li>
          <transition name="el-fade-in-linear">
            <div ref="tooltip" class="el-popper el-popover" v-show="popperVisible" @mouseover="clearTime" @mouseout="hide">
              <span>{{iconText}}</span>
              <span class="el-popper__arrow" data-popper-arrow="" style="position: absolute; left: 0px; transform: translate(46px, 0px);"></span>
            </div>
          </transition>
            <div v-if="iconList.length == 0" style="margin: 0 auto">暂无图标</div>
        </ul>
        </el-scrollbar>
    </el-popover>
</template>

<script>
    import { createPopper } from '@popperjs/core';
    import {defineComponent,ref,watch} from 'vue'
    export default defineComponent({
        name: "EadminIcon",
        props: {
            modelValue: [String,Number],
        },
        emits: ['update:modelValue'],
        setup(props,ctx){
            const fontAwesome = ["slack", "arrows-alt", "wpexplorer", "video-camera", "cutlery", "times-rectangle-o", "coffee", "play-circle", "chain", "hand-rock-o", "list-ul", "sitemap", "step-backward", "columns", "arrow-left", "fa", "italic", "turkish-lira", "github-square", "mobile", "file-o", "paw", "tree", "remove", "adn", "google-plus", "external-link", "maxcdn", "battery-three-quarters", "cc", "wpbeginner", "universal-access", "hand-grab-o", "vine", "hacker-news", "sticky-note", "caret-square-o-right", "yahoo", "shopping-basket", "code-fork", "user", "codepen", "reorder", "cc-mastercard", "laptop", "sheqel", "chevron-circle-left", "meh-o", "spoon", "cloud", "file-pdf-o", "th-list", "address-book-o", "quote-right", "battery-2", "battery-1", "bookmark", "battery-4", "file-sound-o", "battery-3", "caret-square-o-up", "xing", "battery-0", "upload", "commenting-o", "chevron-circle-right", "times", "pie-chart", "leanpub", "glass", "toggle-left", "hand-o-right", "file-code-o", "hand-spock-o", "asl-interpreting", "pencil", "calendar", "i-cursor", "shirtsinbulk", "caret-up", "snapchat-ghost", "user-circle", "user-times", "tencent-weibo", "close", "tags", "skype", "ge", "digg", "ravelry", "binoculars", "gg", "soccer-ball-o", "google", "beer", "contao", "mars-stroke", "cube", "align-justify", "file-archive-o", "toggle-on", "whatsapp", "suitcase", "pencil-square", "font", "eercast", "trello", "calendar-plus-o", "arrow-circle-left", "pencil-square-o", "connectdevelop", "bullhorn", "thermometer-half", "chevron-left", "stack-exchange", "book", "arrows", "window-close-o", "shekel", "git", "scissors", "fast-forward", "cc-amex", "car", "tint", "outdent", "flickr", "arrow-up", "music", "mercury", "html5", "microphone-slash", "simplybuilt", "inr", "send-o", "krw", "long-arrow-up", "thumbs-down", "diamond", "bolt", "hand-pointer-o", "bomb", "paste", "birthday-cake", "jsfiddle", "file-movie-o", "tag", "youtube", "thumbs-o-up", "keyboard-o", "hand-peace-o", "blind", "list-ol", "id-card", "cab", "delicious", "file-powerpoint-o", "dollar", "shield fa-rotate-270", "ils", "backward", "etsy", "circle-thin", "copyright", "folder", "group", "spotify", "television", "vimeo", "hospital-o", "volume-control-phone", "sort-desc", "mail-forward", "twitter", "bluetooth-b", "chevron-circle-up", "ioxhost", "at", "pause", "angle-left", "quora", "eraser", "rss-square", "thermometer-three-quarters", "hdd-o", "gittip", "mobile-phone", "users", "assistive-listening-systems", "caret-square-o-down", "unlock", "play", "superscript", "chevron-right", "sign-in", "paint-brush", "youtube-play", "odnoklassniki", "empire", "deafness", "arrow-circle-up", "photo", "reddit-alien", "shopping-cart", "fire-extinguisher", "share-square", "picture-o", "cc-diners-club", "square", "times-circle-o", "wechat", "search-plus", "window-restore", "sort-alpha-asc", "gbp", "font-awesome", "facebook-official", "quote-left", "thumbs-o-down", "hand-scissors-o", "linux", "steam", "building", "soundcloud", "sticky-note-o", "amazon", "eye-slash", "lightbulb-o", "arrow-circle-o-left", "align-right", "long-arrow-right", "bar-chart-o", "modx", "android", "times-rectangle", "cc-discover", "star-half-o", "firefox", "snowflake-o", "glide-g", "paypal", "pied-piper-alt", "cloud-download", "circle-o", "github", "gratipay", "underline", "key", "magic", "caret-right", "grav", "facebook-f", "address-card", "object-group", "google-plus-square", "btc", "viacoin", "address-book", "battery", "windows", "bus", "sun-o", "strikethrough", "tablet", "bold", "life-bouy", "image", "align-left", "crop", "microphone", "bug", "wpforms", "slideshare", "xing-square", "transgender", "hotel", "file-image-o", "battery-half", "pause-circle-o", "indent", "share-square-o", "codiepie", "rotate-left", "toggle-right", "behance-square", "exchange", "mail-reply-all", "ship", "exclamation", "umbrella", "meanpath", "warning", "spinner", "exclamation-circle", "google-plus-circle", "share-alt-square", "external-link-square", "wheelchair-alt", "low-vision", "step-forward", "asterisk", "angle-double-down", "sort-amount-asc", "arrows-v", "support", "s15", "undo", "signing", "tachometer", "long-arrow-left", "comment-o", "flask", "flash", "youtube-square", "arrows-h", "steam-square", "dedent", "hard-of-hearing", "dashcube", "language", "newspaper-o", "trophy", "forumbee", "genderless", "angle-double-right", "imdb", "automobile", "list", "calendar-check-o", "heart", "pinterest", "vcard", "pinterest-square", "flag-checkered", "user-circle-o", "mars-double", "circle", "envelope-square", "briefcase", "check-circle", "check-square", "houzz", "calendar-o", "paperclip", "caret-left", "money", "id-badge", "expeditedssl", "calendar-times-o", "credit-card", "sort-down", "map", "clock-o", "rupee", "usd", "save", "terminal", "venus-mars", "bicycle", "graduation-cap", "usb", "window-close", "shield fa-rotate-90", "database", "yelp", "thermometer-empty", "text-height", "gear", "share-alt", "star-half-empty", "intersex", "sort-alpha-desc", "reddit-square", "retweet", "foursquare", "sellsy", "minus", "share", "neuter", "phone-square", "volume-down", "paper-plane-o", "linode", "gift", "bluetooth", "floppy-o", "gears", "arrow-circle-right", "hand-o-left", "weixin", "crosshairs", "bell-o", "puzzle-piece", "industry", "stack-overflow", "tasks", "drupal", "hand-o-down", "battery-full", "smile-o", "align-center", "link", "power-off", "stop", "chevron-circle-down", "handshake-o", "moon-o", "resistance", "y-combinator-square", "hourglass-start", "signal", "paper-plane", "desktop", "life-buoy", "microchip", "qrcode", "random", "won", "bitcoin", "arrow-circle-o-up", "user-md", "git-square", "adjust", "search-minus", "odnoklassniki-square", "battery-empty", "pied-piper-pp", "opencart", "camera", "square-o", "sort-asc", "info-circle", "eyedropper", "instagram", "lastfm", "folder-open-o", "thermometer-4", "star-o", "bell-slash", "google-wallet", "angle-down", "file-audio-o", "sort-numeric-desc", "plus-square-o", "reply", "chevron-up", "mixcloud", "bed", "question-circle-o", "cc-jcb", "chevron-down", "thermometer-full", "trash", "arrow-circle-down", "forward", "file-word-o", "id-card-o", "podcast", "glide", "comments-o", "wheelchair", "long-arrow-down", "unlink", "snapchat-square", "location-arrow", "ban", "envelope-open-o", "google-plus-official", "file-video-o", "window-minimize", "caret-down", "thermometer-1", "thermometer-0", "thermometer-3", "thermometer-2", "bar-chart", "question-circle", "black-tie", "cloud-upload", "tripadvisor", "file-text-o", "lemon-o", "wordpress", "mars", "first-order", "envelope-open", "barcode", "expand", "plane", "arrow-right", "map-marker", "euro", "unsorted", "joomla", "bath", "meetup", "chrome", "repeat", "toggle-down", "rouble", "download", "life-ring", "shield fa-flip-vertical", "globe", "jpy", "arrow-down", "shield", "balance-scale", "apple", "fort-awesome", "tumblr", "file-photo-o", "stop-circle-o", "stumbleupon", "header", "twitch", "venus", "openid", "institution", "question", "chain-broken", "recycle", "skyatlas", "file-excel-o", "bars", "hand-stop-o", "frown-o", "paragraph", "print", "circle-o-notch", "clipboard", "inbox", "sign-out", "navicon", "drivers-license-o", "legal", "leaf", "flag", "hand-lizard-o", "bookmark-o", "copy", "scribd", "mars-stroke-v", "shield fa-rotate-180", "life-saver", "envelope", "sort-amount-desc", "comments", "500px", "reply-all", "map-pin", "send", "arrow-circle-o-right", "university", "credit-card-alt", "road", "trash-o", "cart-plus", "futbol-o", "fax", "wifi", "user-o", "percent", "mars-stroke-h", "refresh", "medkit", "safari", "server", "mouse-pointer", "files-o", "dot-circle-o", "buysellads", "gamepad", "train", "times-circle", "angle-double-up", "braille", "product-hunt", "cubes", "eject", "cc-stripe", "address-card-o", "yen", "pagelines", "battery-quarter", "code", "rebel", "wikipedia-w", "th-large", "thermometer", "history", "unlock-alt", "angellist", "minus-circle", "edit", "hourglass-half", "phone", "vk", "user-secret", "male", "internet-explorer", "plus", "shower", "sort", "rotate-right", "dropbox", "feed", "bullseye", "sign-language", "comment", "level-up", "heart-o", "themeisle", "subscript", "wrench", "file-text", "shield fa-flip-horizontal", "american-sign-language-interpreting", "edge", "building-o", "tv", "certificate", "reddit", "th", "viadeo-square", "calculator", "minus-square-o", "archive", "rocket", "sort-numeric-asc", "caret-square-o-left", "cogs", "twitter-square", "heartbeat", "headphones", "cc-visa", "anchor", "motorcycle", "shopping-bag", "viadeo", "angle-up", "superpowers", "tumblr-square", "commenting", "rss", "play-circle-o", "flag-o", "mail-reply", "gg-circle", "thermometer-quarter", "rub", "sort-up", "pinterest-p", "volume-up", "text-width", "get-pocket", "level-down", "renren", "css3", "bathtub", "vimeo-square", "taxi", "gitlab", "fast-backward", "area-chart", "stethoscope", "pause-circle", "deviantart", "h-square", "weibo", "fire", "angle-right", "cart-arrow-down", "bank", "cut", "mortar-board", "yc", "toggle-off", "window-maximize", "star", "exclamation-triangle", "eye", "trademark", "bitbucket", "stumbleupon-circle", "compass", "female", "folder-o", "audio-description", "home", "envelope-o", "filter", "registered", "check-square-o", "bitbucket-square", "map-o", "vcard-o", "dribbble", "bandcamp", "snapchat", "arrow-circle-o-down", "plus-circle", "bell", "venus-double", "transgender-alt", "envira", "yc-square", "tty", "compress", "fonticons", "toggle-up", "space-shuttle", "truck", "street-view", "folder-open", "hashtag", "facebook-square", "minus-square", "file-zip-o", "cc-paypal", "hourglass-end", "subway", "info", "facebook", "eur", "github-alt", "search", "clone", "try", "thumb-tack", "behance", "linkedin", "ellipsis-h", "ra", "hand-o-up", "hourglass-o", "star-half-full", "object-ungroup", "creative-commons", "qq", "fighter-jet", "file-picture-o", "linkedin-square", "opera", "plus-square", "y-combinator", "magnet", "rmb", "user-plus", "ambulance", "sliders", "free-code-camp", "file", "child", "ticket", "pied-piper", "gavel", "list-alt", "film", "cog", "line-chart", "check-circle-o", "cny", "ellipsis-v", "plug", "thumbs-up", "yoast", "optin-monster", "lastfm-square", "medium", "hourglass-1", "drivers-license", "table", "hourglass-2", "hourglass-3", "ruble", "check", "stop-circle", "lock", "calendar-minus-o", "bell-slash-o", "star-half", "angle-double-left", "hourglass", "telegram", "map-signs", "camera-retro", "dashboard", "hand-paper-o", "volume-off"].map(s => "fa fa-" + s);
            const elementUI = ["platform-eleme", "eleme", "delete-solid", "delete", "s-tools", "setting", "user-solid", "user", "phone", "phone-outline", "more", "more-outline", "star-on", "star-off", "s-goods", "goods", "warning", "warning-outline", "question", "info", "remove", "circle-plus", "success", "error", "zoom-in", "zoom-out", "remove-outline", "circle-plus-outline", "circle-check", "circle-close", "s-help", "help", "minus", "plus", "check", "close", "picture", "picture-outline", "picture-outline-round", "upload", "upload2", "download", "camera-solid", "camera", "video-camera-solid", "video-camera", "message-solid", "bell", "s-cooperation", "s-order", "s-platform", "s-fold", "s-unfold", "s-operation", "s-promotion", "s-home", "s-release", "s-ticket", "s-management", "s-open", "s-shop", "s-marketing", "s-flag", "s-comment", "s-finance", "s-claim", "s-custom", "s-opportunity", "s-data", "s-check", "s-grid", "menu", "share", "d-caret", "caret-left", "caret-right", "caret-bottom", "caret-top", "bottom-left", "bottom-right", "back", "right", "bottom", "top", "top-left", "top-right", "arrow-left", "arrow-right", "arrow-down", "arrow-up", "d-arrow-left", "d-arrow-right", "video-pause", "video-play", "refresh", "refresh-right", "refresh-left", "finished", "sort", "sort-up", "sort-down", "rank", "loading", "view", "c-scale-to-original", "date", "edit", "edit-outline", "folder", "folder-opened", "folder-add", "folder-remove", "folder-delete", "folder-checked", "tickets", "document-remove", "document-delete", "document-copy", "document-checked", "document", "document-add", "printer", "paperclip", "takeaway-box", "search", "monitor", "attract", "mobile", "scissors", "umbrella", "headset", "brush", "mouse", "coordinate", "magic-stick", "reading", "data-line", "data-board", "pie-chart", "data-analysis", "collection-tag", "film", "suitcase", "suitcase-1", "receiving", "collection", "files", "notebook-1", "notebook-2", "toilet-paper", "office-building", "school", "table-lamp", "house", "no-smoking", "smoking", "shopping-cart-full", "shopping-cart-1", "shopping-cart-2", "shopping-bag-1", "shopping-bag-2", "sold-out", "sell", "present", "box", "bank-card", "money", "coin", "wallet", "discount", "price-tag", "news", "guide", "male", "female", "thumb", "cpu", "link", "connection", "open", "turn-off", "set-up", "chat-round", "chat-line-round", "chat-square", "chat-dot-round", "chat-dot-square", "chat-line-square", "message", "postcard", "position", "turn-off-microphone", "microphone", "close-notification", "bangzhu", "time", "odometer", "crop", "aim", "switch-button", "full-screen", "copy-document", "mic", "stopwatch", "medal-1", "medal", "trophy", "trophy-1", "first-aid-kit", "discover", "place", "location", "location-outline", "location-information", "add-location", "delete-location", "map-location", "alarm-clock", "timer", "watch-1", "watch", "lock", "unlock", "key", "service", "mobile-phone", "bicycle", "truck", "ship", "basketball", "football", "soccer", "baseball", "wind-power", "light-rain", "lightning", "heavy-rain", "sunrise", "sunrise-1", "sunset", "sunny", "cloudy", "partly-cloudy", "cloudy-and-sunny", "moon", "moon-night", "dish", "dish-1", "food", "chicken", "fork-spoon", "knife-fork", "burger", "tableware", "sugar", "dessert", "ice-cream", "hot-water", "water-cup", "coffee-cup", "cold-drink", "goblet", "goblet-full", "goblet-square", "goblet-square-full", "refrigerator", "grape", "watermelon", "cherry", "apple", "pear", "orange", "coffee", "ice-tea", "ice-drink", "milk-tea", "potato-strips", "lollipop", "ice-cream-square", "ice-cream-round"].map(s => "el-icon-" + s);
            let iconList = ref(fontAwesome.concat(elementUI))
            const value = ref(props.modelValue)
            const tooltip = ref('')
            const iconText = ref('')
            const visible = ref(false)
            const popperVisible = ref(false)
            let timer = null
            watch(value,(val)=>{
                ctx.emit('update:modelValue', val)
            })
            function filterIcons() {
                iconList.value = fontAwesome.concat(elementUI).filter(item=>{
                    return item.indexOf(value.value) >= 0
                })
            }
            function selectIcon(icon){
                value.value = icon
                visible.value = false
            }
            function clearTime(){
                clearTimeout(timer)
            }
            function hide(){
              timer = setTimeout(()=>{
                popperVisible.value = false
              },1000)
            }
            function popper(e,text){
              clearTimeout(timer)
              iconText.value = text
              popperVisible.value = true
              createPopper(e.target, tooltip.value, {
                placement: 'bottom',
                modifiers: [
                  {
                    name: 'offset',
                    options: {
                      offset: [0, 12],
                    },
                  },
                ],
              })
            }
            return {
                clearTime,
                hide,
                iconText,
                tooltip,
                popper,
                value,
                iconList,
                selectIcon,
                visible,
                popperVisible,
                filterIcons

            }
        }

    })
</script>

<style scoped>
.iconCollection{
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    width: 910px;
    height: 400px;
}

.iconCollection li{
    cursor: pointer;
    width: 40px;
    height: 40px;
    border:1px solid #ededed;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.iconCollection i{
    font-size: 20px;
}
.iconText{
     margin-top: 10px;
}
</style>
