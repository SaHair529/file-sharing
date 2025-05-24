let selectedFiles = []

document.querySelector('#file').addEventListener('change', function (e) {
    const files = e.target.files
    const $filesGallery = document.querySelector('.files-gallery')
    const $uploadFilesBtn = document.querySelector('.upload-files-btn')

    $filesGallery.innerHTML = ''

    selectedFiles = Array.from(files)

    selectedFiles.forEach((file, i) => {
        const $fileItem = document.createElement('div')
        $fileItem.classList.add('file-item')
        $fileItem.title = file.name
        $fileItem.innerHTML = `
            <button class="file-item__remove" onclick="removeFile(${i})">×</button>
            <div class="file-item__image">${getFileEmoji(file.name)}</div>
            <div class="file-item__name">${file.name}</div>
        `
        $filesGallery.appendChild($fileItem)
    })

    $filesGallery.hidden = false
    $uploadFilesBtn.hidden = false
})

document.querySelector('.upload-files-btn').addEventListener('click', function () {
    const url = document.querySelector('form').getAttribute('action')
    const formData = new FormData()
    selectedFiles.forEach(file => {
        formData.append('files[]', file)
    })

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            response.json().then(responseData => {
                selectedFiles = []
                document.querySelector('#file').value = ''
                document.querySelector('.files-gallery').innerHTML = ''
                document.querySelector('.files-gallery').hidden = true
                document.querySelector('.upload-files-btn').hidden = true

                document.querySelector('.upload-result').hidden = false
                document.querySelector('.uploaded-page-link').textContent = responseData.link
            })
        } 
        else {
            alert('Failed to upload files.')
        }
    })
})

document.querySelector('.close-upload-result').addEventListener('click', function () {
    const $uploadResult = document.querySelector('.upload-result')
    $uploadResult.hidden = true
    
    document.querySelector('.uploaded-page-link').textContent = ''
})

function removeFile(index) {
    selectedFiles.splice(index, 1)

    const $filesGallery = document.querySelector('.files-gallery')
    const $uploadFilesBtn = document.querySelector('.upload-files-btn')
    $filesGallery.innerHTML = ''

    selectedFiles.forEach((file, index) => {
        const $fileItem = document.createElement('div')
        $fileItem.classList.add('file-item')
        $fileItem.title = file.name
        $fileItem.innerHTML = `
            <button class="file-item__remove" onclick="removeFile(${index})">×</button>
            <div class="file-item__image">${getFileEmoji(file.name)}</div>
            <div class="file-item__name">${file.name}</div>
        `
        $filesGallery.appendChild($fileItem);
    })

    if (selectedFiles.length === 0) {
        $filesGallery.hidden = true
        $uploadFilesBtn.hidden = true
    }
}

function getFileEmoji(fileName) {
    const fileExtension = fileName.split('.').pop().toLowerCase()

    switch (fileExtension) {
        case 'txt':
        case 'md':
        case 'log':
            return '📝'

        case 'doc':
        case 'docx':
        case 'pdf':
            return '📄'

        case 'xls':
        case 'xlsx':
        case 'csv':
            return '📊'

        case 'ppt':
        case 'pptx':
            return '📑'

        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'svg':
            return '🖼️'

        case 'mp4':
        case 'avi':
        case 'mov':
        case 'mkv':
            return '🎥'

        case 'mp3':
        case 'wav':
        case 'ogg':
            return '🎵'

        case 'zip':
        case 'rar':
        case '7z':
        case 'tar':
        case 'gz':
            return '📦'

        case 'js':
        case 'py':
        case 'html':
        case 'css':
        case 'java':
        case 'c':
        case 'cpp':
        case 'cs':
        case 'go':
        case 'php':
        case 'rb':
        case 'swift':
            return '💻'

        case 'exe':
        case 'sh':
        case 'bat':
            return '⚙️'

        default:
            return '❓'
    }
}