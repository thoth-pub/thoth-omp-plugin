(function () {
    var config = window.thothCatalogFiles;

    if (!config || !config.url) {
        return;
    }

    var downloadsLabel = config.downloadsLabel || 'Downloads';
    var loadingLabel = config.loadingLabel || 'Loading';

    function toArray(items) {
        return Array.prototype.slice.call(items || []);
    }

    function createElement(tag, className, text) {
        var element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text) {
            element.textContent = text;
        }

        return element;
    }

    function getThothLabel(label) {
        return label + ' (Thoth)';
    }

    function getObjectKeyLabel(file) {
        return getThothLabel(file.label);
    }

    function getPublicationTypeLabel(file) {
        return getThothLabel(file.publicationType || file.label);
    }

    function isValidFileUrl(file) {
        if (!file || !file.url || !/^https?:\/\//i.test(file.url)) {
            return false;
        }

        try {
            var url = new URL(file.url, window.location.href);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (error) {
            return false;
        }
    }

    function createLink(file, label) {
        var link = createElement('a', 'cmp_download_link', label || getObjectKeyLabel(file));

        link.href = file.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        return link;
    }

    function createPublicationFormatItem(label, link, isThothFile) {
        var item = document.createElement('li');
        var name = createElement('span', 'name', label);
        var linkWrapper = createElement('span', 'link');

        if (isThothFile) {
            item.setAttribute('data-thoth-file', 'true');
        }

        linkWrapper.appendChild(link);
        item.appendChild(name);
        item.appendChild(linkWrapper);

        return item;
    }

    function getRemotePublicationFormatClass(file) {
        var className = 'pub_format_remote';

        if (file.representationId) {
            className = 'pub_format_' + file.representationId + ' ' + className;
        }

        return className;
    }

    function renderFiles(container, files, options) {
        options = options || {};

        if (!container || !files || !files.length) {
            return;
        }

        container.textContent = '';

        if (options.includeHeading) {
            container.appendChild(createElement('h2', 'pkp_screen_reader', downloadsLabel));
        }

        files.forEach(function (file) {
            if (!isValidFileUrl(file)) {
                return;
            }

            var wrapper = document.createElement('div');

            if (options.remotePublicationFormat) {
                wrapper.className = getRemotePublicationFormatClass(file);
            }

            wrapper.appendChild(createLink(file, (options.labelGetter || getObjectKeyLabel)(file)));
            container.appendChild(wrapper);
        });
    }

    function renderMonographFiles(files) {
        var fallbackFiles = [];

        (files || []).forEach(function (file) {
            if (!renderFileInPublicationFormat(file)) {
                fallbackFiles.push(file);
            }
        });

        if (fallbackFiles.length) {
            renderFiles(getMonographTarget(), fallbackFiles, {
                labelGetter: getPublicationTypeLabel,
                remotePublicationFormat: true
            });
        }
    }

    function renderFileInPublicationFormat(file) {
        if (!isValidFileUrl(file) || !file.representationId) {
            return false;
        }

        var value = getPublicationFormatValue(file);
        var list = value && value.querySelector('ul');

        if (!list) {
            return false;
        }

        list.appendChild(createPublicationFormatItem(
            getObjectKeyLabel(file),
            createLink(file),
            true
        ));

        return true;
    }

    function getPublicationFormatValue(file) {
        var selector = '.entry_details .pub_format_' + file.representationId;
        var value = document.querySelector(selector + ' > .value');

        if (value && value.querySelector('ul')) {
            return value;
        }

        var single = document.querySelector(selector + '.pub_format_single');
        return single ? convertSinglePublicationFormat(single) : null;
    }

    function convertSinglePublicationFormat(single) {
        var existingLink = single.querySelector('a');

        if (!existingLink) {
            return null;
        }

        var existingLabel = existingLink.textContent.trim();
        var publicationFormatId = getPublicationFormatId(single);
        var existingFileName = getPublicationFormatFileName(publicationFormatId, existingLabel);
        var label = createElement('span', 'label', existingLabel);
        var value = createElement('span', 'value');
        var list = document.createElement('ul');

        single.className = single.className.replace(/\s*pub_format_single\s*/, ' ').trim();
        single.textContent = '';

        list.appendChild(createPublicationFormatItem(existingFileName, existingLink, false));
        value.appendChild(list);
        single.appendChild(label);
        single.appendChild(value);

        return value;
    }

    function getPublicationFormatId(element) {
        var match = element.className.match(/(?:^|\s)pub_format_(\d+)(?:\s|$)/);

        return match ? match[1] : null;
    }

    function getPublicationFormatFileName(publicationFormatId, fallbackLabel) {
        var files = (config.publicationFormatFiles || {})[publicationFormatId] || [];

        return files[0] || fallbackLabel;
    }

    function getTargetSelector(target, id) {
        var selector = '[data-thoth-target="' + target + '"]';

        if (id !== undefined) {
            selector += '[data-chapter-id="' + id + '"]';
        }

        return selector;
    }

    function getChapterIndex(chapterId) {
        var chapters = config.chapters || [];

        for (var i = 0; i < chapters.length; i++) {
            if (String(chapters[i].id) === String(chapterId)) {
                return i;
            }
        }

        return -1;
    }

    function getChapterTarget(chapterId) {
        var existingTarget = document.querySelector(getTargetSelector('chapter', chapterId));

        if (existingTarget) {
            return existingTarget;
        }

        var chapterItem = document.querySelectorAll('.item.chapters > ul > li')[getChapterIndex(chapterId)];

        if (!chapterItem) {
            return null;
        }

        var target = createElement('div', 'files thoth_files');
        target.setAttribute('data-thoth-target', 'chapter');
        target.setAttribute('data-chapter-id', chapterId);
        chapterItem.appendChild(target);

        return target;
    }

    function hasChapterPublicationFiles(target) {
        if (!target || !target.parentNode) {
            return false;
        }

        var fileBlocks = target.parentNode.querySelectorAll('.files');

        for (var i = 0; i < fileBlocks.length; i++) {
            if (
                fileBlocks[i] !== target
                && fileBlocks[i].className.indexOf('thoth_files') < 0
                && fileBlocks[i].querySelector('.cmp_download_link')
            ) {
                return true;
            }
        }

        return false;
    }

    function getMonographTarget() {
        var existingTarget = document.querySelector(getTargetSelector('monograph'));

        if (existingTarget) {
            return existingTarget;
        }

        var filesBlock = getOrCreateMonographFilesBlock();

        if (!filesBlock) {
            return null;
        }

        var target = createElement('div', 'thoth_files');
        target.setAttribute('data-thoth-target', 'monograph');
        filesBlock.appendChild(target);

        return target;
    }

    function getOrCreateMonographFilesBlock() {
        var filesBlock = document.querySelector('.entry_details > .item.files');

        if (filesBlock) {
            return filesBlock;
        }

        var entryDetails = document.querySelector('.entry_details');

        if (!entryDetails) {
            return null;
        }

        filesBlock = createElement('div', 'item files');
        filesBlock.setAttribute('data-thoth-created', 'monograph-files');
        filesBlock.appendChild(createElement('h2', 'pkp_screen_reader', downloadsLabel));
        entryDetails.insertBefore(filesBlock, entryDetails.querySelector('.item.date_published'));

        return filesBlock;
    }

    function renderLoading(container) {
        if (!container) {
            return;
        }

        container.textContent = '';
        container.appendChild(createElement('span', 'thoth_files_loading', loadingLabel));
    }

    function renderLoadingTargets() {
        renderLoading(getMonographTarget());

        (config.chapters || []).forEach(function (chapter) {
            renderLoading(getChapterTarget(chapter.id));
        });
    }

    function clearEmptyTargets(filesByChapter) {
        clearMonographTarget();

        (config.chapters || []).forEach(function (chapter) {
            var chapterId = String(chapter.id);
            var files = filesByChapter[chapterId] || [];
            var target = document.querySelector(getTargetSelector('chapter', chapterId));

            if (target && !files.length) {
                target.textContent = '';
            }
        });
    }

    function clearMonographTarget() {
        var target = getMonographTarget();

        if (!target) {
            return;
        }

        target.textContent = '';

        if (target.parentNode && target.parentNode.getAttribute('data-thoth-created') === 'monograph-files') {
            target.parentNode.parentNode.removeChild(target.parentNode);
        }
    }

    function getCacheKey() {
        return 'thothCatalogFiles:' + config.url + ':' + (config.cacheKeySuffix || '');
    }

    function getCachedContent() {
        try {
            var cache = JSON.parse(localStorage.getItem(getCacheKey()));
            var ttl = (config.cacheTtl || 3600) * 1000;

            if (cache && cache.createdAt && Date.now() - cache.createdAt < ttl) {
                return cache.content;
            }
        } catch (error) {
            return null;
        }

        return null;
    }

    function setCachedContent(content) {
        try {
            localStorage.setItem(getCacheKey(), JSON.stringify({
                createdAt: Date.now(),
                content: content
            }));
        } catch (error) {
            return;
        }
    }

    function removeRenderedCatalogFiles() {
        toArray(document.querySelectorAll('[data-thoth-file="true"]')).forEach(function (item) {
            item.parentNode.removeChild(item);
        });
    }

    function renderContent(content) {
        var chapters = content.chapters || {};

        removeRenderedCatalogFiles();
        clearEmptyTargets(chapters);
        renderMonographFiles(content.monograph);

        Object.keys(chapters).forEach(function (chapterId) {
            var target = getChapterTarget(chapterId);

            renderFiles(target, chapters[chapterId], {
                labelGetter: hasChapterPublicationFiles(target) ? getObjectKeyLabel : getPublicationTypeLabel
            });
        });
    }

    function clearTargetsWhenNoCache(cachedContent) {
        if (!cachedContent) {
            clearEmptyTargets({});
        }
    }

    function loadFiles() {
        var cachedContent = getCachedContent();

        if (cachedContent) {
            renderContent(cachedContent);
        } else {
            renderLoadingTargets();
        }

        fetch(config.url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (!response.status || !response.content) {
                    clearTargetsWhenNoCache(cachedContent);
                    return;
                }

                setCachedContent(response.content);
                renderContent(response.content);
            })
            .catch(function () {
                clearTargetsWhenNoCache(cachedContent);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadFiles);
    } else {
        loadFiles();
    }
}());
