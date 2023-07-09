// Main JS file

// Image lightbox
const modal = document.getElementById('imageModal')
const modalImage = document.getElementById('modalImage')
const imageLinks = Array.from(document.getElementsByClassName('gallery'))
let currentIndex = 0

const handleArrowKeys = event => {
    if (event.key === 'ArrowRight') {
        currentIndex = (currentIndex + 1) % imageLinks.length
    } else if (event.key === 'ArrowLeft') {
        currentIndex = (currentIndex - 1 + imageLinks.length) % imageLinks.length
    } else {
        return
    }
    const newSrc = imageLinks[currentIndex].getAttribute('href')
    modalImage.setAttribute('src', newSrc)
}

modal.addEventListener('show.bs.modal', event => {
    const imageLink = event.relatedTarget
    const src = imageLink.getAttribute('href')
    modalImage.setAttribute('src', src)
    currentIndex = imageLinks.indexOf(imageLink)
    document.addEventListener('keyup', handleArrowKeys)
})

modal.addEventListener('hide.bs.modal', () => {
    document.removeEventListener('keyup', handleArrowKeys)
})
