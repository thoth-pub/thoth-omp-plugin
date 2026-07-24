import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const componentSource = readFileSync(
	new URL('../../resources/js/Components/ThothSection.vue', import.meta.url),
	'utf8',
);
const englishLocale = readFileSync(
	new URL('../../locale/en/locale.po', import.meta.url),
	'utf8',
);
const portugueseLocale = readFileSync(
	new URL('../../locale/pt_BR/locale.po', import.meta.url),
	'utf8',
);
const templateSource = componentSource.match(
	/<template>([\s\S]*?)<\/template>/,
)[1];

test('uses the short missing Work labels in English and Portuguese', () => {
	assert.match(
		englishLocale,
		/msgid "plugins\.generic\.thoth\.status\.notFound"\nmsgstr "Not found"/,
	);
	assert.match(
		portugueseLocale,
		/msgid "plugins\.generic\.thoth\.status\.notFound"\nmsgstr "Não encontrado"/,
	);
});

test('uses the standard OMP button markup for every Thoth action', () => {
	const actionTags = templateSource.match(/<PkpButton\b[\s\S]*?>/g) || [];

	assert.equal(actionTags.length, 4);
	actionTags.forEach((actionTag) =>
		assert.match(actionTag, /\bis-link\b/),
	);
	assert.doesNotMatch(templateSource, /<a\b/);
	assert.doesNotMatch(templateSource, /\belement="a"/);
	assert.match(
		templateSource,
		/<PkpButton\s+v-if="actionVisibility\.view"\s+is-link\s+@click="viewWork"/,
	);
});
