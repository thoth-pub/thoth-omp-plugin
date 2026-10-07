const helperPath = 'plugins/generic/thoth/cypress/support/ThothTestData.php';
const quote = (value) => `'${String(value).replace(/'/g, `'"'"'`)}'`;

const runHelper = (operation, key) => {
	const args = ['php', helperPath, operation];
	if (key) {
		args.push(key);
	}
	return cy.exec(args.map(quote).join(' '), {log: false})
		.then(({stdout}) => JSON.parse(stdout.trim()));
};

export const seedPublishedBook = (group) => runHelper('create', group);
export const seedCompleteBook = () => runHelper('create-complete');
export const seedDraftBook = () => runHelper('create-draft');
export const seedLinkedDraftBook = () => runHelper('create-linked-draft');
export const readBookState = (key) => runHelper('inspect', key);
export const readRegisteredWork = (key) => runHelper('verify', key);
