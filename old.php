<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modern English-Bangla Dictionary</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>

    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #555; }
</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans transition-colors duration-300">

<div class="flex min-h-screen">

    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-800 shadow-lg p-5 transform -translate-x-full transition-transform md:translate-x-0 z-50">
        <h2 class="text-xl font-bold mb-4">History</h2>
        <ul id="history" class="space-y-2 text-gray-700 dark:text-gray-200 overflow-y-auto max-h-[60vh]"></ul>
        <button onclick="clearHistory()" class="mt-4 w-full bg-red-500 text-white p-2 rounded hover:bg-red-600">Clear History</button>
        <div class="mt-6">
            <button onclick="toggleDarkMode()" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Toggle Dark/Light Mode</button>
        </div>
    </div>

    <button onclick="toggleSidebar()" class="md:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-2 rounded shadow hover:bg-blue-700">☰</button>

    <div class="flex-1 p-6 md:ml-64">

        <div class="mb-6 flex flex-col md:flex-row gap-2 items-center">
            <input id="searchWord" type="text" placeholder="Search a word..." 
                class="w-full md:w-1/2 p-3 rounded border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
            <button onclick="searchWord()" 
                class="px-4 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">Search</button>
            <button onclick="startListening()" 
                class="px-3 py-3 bg-gray-800 text-white rounded hover:bg-gray-900 ml-2">🎤</button>
        </div>

        <div id="wordContent" class="space-y-6"></div>
    </div>
</div>

<script>

function toggleSidebar() { document.getElementById('sidebar').classList.toggle('-translate-x-full'); }

function toggleDarkMode() { document.body.classList.toggle('dark'); }

function loadHistory() {
    const historyList = document.getElementById('history');
    historyList.innerHTML = '';
    const history = JSON.parse(localStorage.getItem('dictHistory') || '[]');
    history.forEach(word => {
        const li = document.createElement('li');
        li.textContent = word;
        li.className = "cursor-pointer hover:text-blue-500";
        li.onclick = () => { document.getElementById('searchWord').value = word; searchWord(word); };
        historyList.appendChild(li);
    });
}

function addHistory(word) {
    let history = JSON.parse(localStorage.getItem('dictHistory') || '[]');
    history = history.filter(w => w !== word);
    history.unshift(word);
    if (history.length > 20) history.pop();
    localStorage.setItem('dictHistory', JSON.stringify(history));
    loadHistory();
}

function clearHistory() { localStorage.removeItem('dictHistory'); loadHistory(); }

function startListening() {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
        alert("Sorry, your browser doesn't support speech recognition."); return;
    }
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'en-US'; recognition.interimResults = false; recognition.maxAlternatives = 1;
    recognition.start();
    recognition.onresult = function(event) {
        const spokenWord = event.results[0][0].transcript.trim();
        document.getElementById('searchWord').value = spokenWord;
        searchWord(spokenWord);
    };
    recognition.onerror = function(event) {
        console.error("Speech recognition error:", event.error);
        alert("Error recognizing speech: " + event.error);
    };
}

async function searchWord(inputWord) {
    const word = inputWord || document.getElementById('searchWord').value.trim();
    if (!word) return;
    const res = await fetch(`api.php?word=${encodeURIComponent(word)}`);
    const data = await res.json();
    if (!data.word) { document.getElementById('wordContent').innerHTML = '<p class="text-red-500">Word not found</p>'; return; }
    addHistory(data.word);
    renderWord(data);
}

function renderWord(data) {
    const container = document.getElementById('wordContent');
    container.innerHTML = '';

    const html = `
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4 transition-colors duration-300">
        <h1 class="text-3xl font-bold">${data.word}</h1>

        <div class="flex flex-col md:flex-row gap-4">
            <div>
                <p class="font-semibold">Bangla Pronunciation:</p>
                <p>${data.bangla_translation?.pronunciation || '-'}</p>
                <div class="flex gap-2 mt-1">
                    ${data.bangla_translation?.pron_audio?.uk ? `<button onclick="playAudio('${data.bangla_translation.pron_audio.uk}')" class="px-2 py-1 bg-green-500 text-white rounded">UK</button>` : ''}
                    ${data.bangla_translation?.pron_audio?.us ? `<button onclick="playAudio('${data.bangla_translation.pron_audio.us}')" class="px-2 py-1 bg-green-500 text-white rounded">US</button>` : ''}
                </div>
            </div>
            <div>
                <p class="font-semibold">English Pronunciation:</p>
                <p>${data.english_pronunciation?.phonetic || '-'}</p>
                <div class="flex gap-2 mt-1">
                    ${data.english_pronunciation?.phonetics?.map(p => p.audio ? `<button onclick="playAudio('${p.audio}')" class="px-2 py-1 bg-blue-500 text-white rounded mr-1">${p.text}</button>` : '').join('') || ''}
                </div>
            </div>
        </div>

        <div>
            <p class="font-semibold">Bangla Translation 1:</p>
            <p>${data.bangla_translation?.translated || '-'}</p>
            <p class="font-semibold mt-2">Bangla Translation 2:</p>
            <p>${data.bangla_translation2 || '-'}</p>
        </div>

        <div>
            <p class="font-semibold">Definitions:</p>
            ${data.parts_of_speech?.map(pos => `
                <div class="mt-2">
                    <p class="font-semibold italic">${pos.partOfSpeech || ''}</p>
                    <ol class="list-decimal ml-5 space-y-1 max-h-48 overflow-y-auto">
                        ${pos.definitions?.map(d => `<li>${d}</li>`).join('') || '-'}
                    </ol>
                </div>
            `).join('') || '-'}
        </div>

        <div class="flex flex-col md:flex-row gap-4 mt-4">
            <div>
                <p class="font-semibold">Synonyms:</p>
                <p>${data.synonyms?.join(', ') || '-'}</p>
            </div>
            <div>
                <p class="font-semibold">Antonyms:</p>
                <p>${data.antonyms?.join(', ') || '-'}</p>
            </div>
        </div>

        <div>
            <p class="font-semibold">Urban Meaning:</p>
            <p>${data.urban_meaning || '-'}</p>
        </div>

        <div>
            <p class="font-semibold">Examples:</p>
            <ul class="list-disc ml-5 max-h-64 overflow-y-auto space-y-1">
                ${data.examples?.map(e => `<li>${e}</li>`).join('') || '-'}
            </ul>
        </div>

        <div>
            <p class="font-semibold mb-2">Images:</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 overflow-x-auto">
                ${data.images?.map(img => `<img src="${img}" alt="${data.word}" class="w-full h-32 object-cover rounded shadow hover:scale-105 transition-transform duration-200 cursor-pointer" onclick="window.open('${img}', '_blank')">`).join('') || '<p>No images found</p>'}
            </div>
        </div>
    </div>
    `;
    container.innerHTML = html;
}

function playAudio(url) {
    const audio = new Audio(url);
    audio.play();
}

loadHistory();
</script>
</body>
</html>