const carrousel = document.querySelector('.carrousel');
const items = document.querySelectorAll('.carrousel a');
const prevBtn = document.querySelector('.carrousel-btn.prev');
const nextBtn = document.querySelector('.carrousel-btn.next');
const dotsContainer = document.querySelector('.carrousel-dots');

let index = 0;
const visibleItems = 5;

const totalSlides = Math.ceil(items.length / visibleItems);

/* Dots */
for (let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('span');
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(i));
    dotsContainer.appendChild(dot);
}

function updateDots() {
    dotsContainer.querySelectorAll('span').forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

function goToSlide(i) {
    index = i;
    const itemWidth = items[0].offsetWidth + 40;
    carrousel.style.transform = `translateX(-${index * visibleItems * itemWidth}px)`;
    updateDots();
}

nextBtn.addEventListener('click', () => {
    index = (index + 1) % totalSlides;
    goToSlide(index);
});

prevBtn.addEventListener('click', () => {
    index = (index - 1 + totalSlides) % totalSlides;
    goToSlide(index);
});

/* Auto scroll */
setInterval(() => {
    index = (index + 1) % totalSlides;
    goToSlide(index);
}, 5000);
