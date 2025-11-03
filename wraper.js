export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const word = url.searchParams.get("word")?.trim();

    const headers = {
      "Content-Type": "application/json; charset=UTF-8",
      "Access-Control-Allow-Origin": "*",
    };

    if (!word) {
      return new Response(JSON.stringify({ error: "No word provided" }), { headers });
    }

    const cacheKey = word.toLowerCase();
    if (!globalThis._cache) globalThis._cache = {};
    const cache = globalThis._cache;

    if (cache[cacheKey] && Date.now() - cache[cacheKey].time < 3600_000) {
      return new Response(cache[cacheKey].data, { headers: { ...headers, "X-Cache": "HIT" } });
    }

    const e2bURL = "https://raw.githubusercontent.com/Nafisa41/Dictionary--English-to-Bangla-/master/Database/E2Bdatabase.json";
    const anomakiURL = `https://www.apis-anomaki.zone.id/tools/translate?word=${encodeURIComponent(word)}&from=EN&to=BN`;
    const dictURL = `https://api.dictionaryapi.dev/api/v2/entries/en/${encodeURIComponent(word)}`;
    const urbanURL = `https://api.urbandictionary.com/v0/define?term=${encodeURIComponent(word)}`;
    const pinURL = `https://www.apis-anomaki.zone.id/search/pinsearch?query=${encodeURIComponent(word)}`;

    const [e2b, anomaki, dict, urban, pin] = await Promise.allSettled([
      fetch(e2bURL).then(r => r.json()).catch(() => null),
      fetch(anomakiURL).then(r => r.json()).catch(() => null),
      fetch(dictURL).then(r => r.json()).catch(() => null),
      fetch(urbanURL).then(r => r.json()).catch(() => null),
      fetch(pinURL).then(r => r.json()).catch(() => null),
    ]);

    const safe = (r) => (r?.status === "fulfilled" ? r.value : null);

    const e2bData = safe(e2b);
    const anomakiData = safe(anomaki);
    const dictData = safe(dict);
    const urbanData = safe(urban);
    const pinData = safe(pin);

    let bangla_translation = {};
    let anomaki_examples = [];
    if (anomakiData?.result) {
      const res = anomakiData.result;
      bangla_translation = {
        translated: res.translated ?? "",
        pronunciation: res.pronunciation ?? "",
        pron_audio: {
          uk: res.uk_audio ?? "",
          us: res.us_audio ?? ""
        }
      };
      if (Array.isArray(res.definitions)) {
        res.definitions.forEach(d => {
          if (d.example?.[0]?.source) anomaki_examples.push(d.example[0].source);
        });
      }
    }

    let bangla_translation2 = "";
    if (e2bData) {
      const lower = word.toLowerCase();
      if (e2bData[lower]?.bn) {
        bangla_translation2 = e2bData[lower].bn;
      } else {
        for (const k in e2bData) {
          if (e2bData[k]?.en?.toLowerCase() === lower) {
            bangla_translation2 = e2bData[k].bn ?? "";
            break;
          }
        }
      }
    }

    const dictionary_data = {
      phonetic: "",
      phonetics: [],
      meanings: [],
      synonyms: [],
      antonyms: []
    };

    if (Array.isArray(dictData) && dictData[0]) {
      const entry = dictData[0];
      dictionary_data.phonetic = entry.phonetic ?? "";
      if (Array.isArray(entry.phonetics)) {
        entry.phonetics.forEach(p => {
          if (p.text || p.audio)
            dictionary_data.phonetics.push({ text: p.text ?? "", audio: p.audio ?? "" });
        });
      }
      if (Array.isArray(entry.meanings)) {
        entry.meanings.forEach(m => {
          const defs = (m.definitions ?? []).map(d => d.definition).filter(Boolean);
          const exs = (m.definitions ?? []).map(d => d.example).filter(Boolean);
          dictionary_data.meanings.push({
            partOfSpeech: m.partOfSpeech ?? "",
            definitions: defs,
            examples: exs
          });
          if (m.synonyms) dictionary_data.synonyms.push(...m.synonyms);
          if (m.antonyms) dictionary_data.antonyms.push(...m.antonyms);
        });
      }
    }

    dictionary_data.synonyms = [...new Set(dictionary_data.synonyms)];
    dictionary_data.antonyms = [...new Set(dictionary_data.antonyms)];

    let urban_meaning = "";
    if (urbanData?.list?.[0]?.definition) {
      urban_meaning = urbanData.list[0].definition.replace(/<\/?[^>]+(>|$)/g, "");
    }

    const images = Array.isArray(pinData?.result) ? pinData.result : [];

    const all_examples = [
      ...(dictionary_data.meanings.flatMap(m => m.examples)),
      ...anomaki_examples
    ].filter(Boolean).slice(0, 20);

    const result = {
      word,
      bangla_translation,
      bangla_translation2,
      english_pronunciation: {
        phonetic: dictionary_data.phonetic,
        phonetics: dictionary_data.phonetics
      },
      parts_of_speech: dictionary_data.meanings,
      synonyms: dictionary_data.synonyms,
      antonyms: dictionary_data.antonyms,
      urban_meaning,
      examples: all_examples,
      images,
      sources: {
        anomaki_api: !!anomakiData,
        dictionaryapi: !!dictData,
        urban: !!urbanData,
        pinsearch: !!pinData,
        e2b_json: !!e2bData
      }
    };

    const jsonOut = JSON.stringify(result, null, 2);
    cache[cacheKey] = { data: jsonOut, time: Date.now() };

    return new Response(jsonOut, { headers: { ...headers, "X-Cache": "MISS" } });
  }
};
