const STORAGE_KEY = 'llama_ocr_archive';

/**
 * Storage functions exposed globally via window or just as top-level functions
 */
function getAllLetters() {
    const letters = localStorage.getItem(STORAGE_KEY);
    return letters ? JSON.parse(letters) : [];
}

function saveLetter(letterData) {
    const letters = getAllLetters();
    const newLetter = {
        ...letterData,
        id: Date.now().toString(),
        createdAt: new Date().toISOString()
    };
    letters.unshift(newLetter); // Add to beginning
    localStorage.setItem(STORAGE_KEY, JSON.stringify(letters));
    return newLetter;
}

function updateLetter(id, updatedData) {
    const letters = getAllLetters();
    const index = letters.findIndex(l => l.id === id);
    if (index !== -1) {
        letters[index] = { ...letters[index], ...updatedData };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(letters));
        return true;
    }
    return false;
}

function deleteLetter(id) {
    const letters = getAllLetters();
    const filtered = letters.filter(l => l.id !== id);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
}

function searchLetters(query) {
    const letters = getAllLetters();
    if (!query) return letters;

    query = query.toLowerCase();
    return letters.filter(l =>
        l.subject.toLowerCase().includes(query) ||
        l.letterNumber.toLowerCase().includes(query) ||
        l.senderService.toLowerCase().includes(query) ||
        l.receiverService.toLowerCase().includes(query)
    );
}
