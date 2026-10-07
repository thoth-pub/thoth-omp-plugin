/**
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. See docs/COPYING.
 */
import {seedPublishedBook, readRegisteredWork, readBookState} from '../../support/thoth';

describe('Thoth bulk registration', function () {
	beforeEach(function () {
		// OMP searches punctuation-separated words with OR; keep the group a single token.
		this.group = `batch${Date.now().toString(36)}${Cypress._.random(0, 65535).toString(16)}`;
		seedPublishedBook(this.group).as('firstBook');
		seedPublishedBook(this.group).as('secondBook');
		seedPublishedBook(this.group).as('unselectedBook');
	});

	it('registers the selected books on the Thoth page and leaves the other book unregistered', function () {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/thoth');

		// Find this run's books and select only two of them.
		cy.intercept('GET', '**/api/v1/_submissions?*').as('search');
		cy.get('.listPanel--thoth input[type="search"]').type(this.group, {delay: 0});
		cy.wait('@search').its('response.statusCode').should('eq', 200);
		cy.get('.listPanel--thoth').within(() => {
			cy.get('input[name="submissions[]"]').should('have.length', 3);
			cy.contains('label', 'Cypress Imprint').find('input[type="radio"]').check();
			cy.get(`input[name="submissions[]"][value="${this.firstBook.submissionId}"]`).check();
			cy.get(`input[name="submissions[]"][value="${this.secondBook.submissionId}"]`).check();
			cy.contains('button', /^\s*Register\s*$/).click();
		});
		cy.intercept('POST', `**/_submissions/${this.firstBook.submissionId}/register`).as('registerFirst');
		cy.intercept('POST', `**/_submissions/${this.secondBook.submissionId}/register`).as('registerSecond');
		cy.intercept('POST', `**/_submissions/${this.unselectedBook.submissionId}/register`).as('registerOther');
		cy.contains('[role="dialog"]', 'Register Submissions').within(() => {
			cy.contains('button', /^\s*Register Submissions\s*$/).click();
		});
		cy.wait(['@registerFirst', '@registerSecond']).each(({response}) => {
			expect(response.statusCode).to.eq(200);
		});

		// Each selected row and its remote work must reflect successful registration.
		[this.firstBook, this.secondBook].forEach((book) => {
			cy.get(`input[name="submissions[]"][value="${book.submissionId}"]`).closest('.listPanel__itemSummary').within(() => {
				cy.contains('Registered').should('be.visible');
				cy.get('input[type="checkbox"]').should('be.disabled');
			});
			readRegisteredWork(book.key).then((work) => {
				expect(work.title).to.eq(book.title);
				expect(work.imprintId).to.eq(book.imprintId);
				expect(work.workStatus).to.eq('ACTIVE');
			});
		});
		cy.get(`input[name="submissions[]"][value="${this.unselectedBook.submissionId}"]`).closest('.listPanel__itemSummary').within(() => {
			cy.contains('Unregistered').should('be.visible');
			cy.get('input[type="checkbox"]').should('not.be.checked').and('not.be.disabled');
		});
		cy.get('@registerOther.all').should('have.length', 0);
		readBookState(this.unselectedBook.key).its('workId').should('be.null');
	});
});
