/**
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. See docs/COPYING.
 */
import {seedCompleteBook, readRegisteredWork} from '../../support/thoth';

describe('Thoth book registration', function () {
	beforeEach(function () {
		seedCompleteBook().as('book');
	});

	it('registers a published monograph with complete metadata using the Register button', function () {
		// Open the publication of an unregistered book.
		cy.login('admin', 'admin', 'publicknowledge');
		cy.intercept('GET', '**/publication-format-grid/fetch-grid*').as('formats');
		cy.visit(`/index.php/publicknowledge/workflow/access/${this.book.submissionId}`);
		cy.get('#publication-button').click();
		cy.get('.pkpPublication').should('be.visible');
		cy.wait('@formats').its('response.statusCode').should('eq', 200);
		cy.intercept('GET', '**/publication-format-grid/fetch-grid*').as('registeredFormats');
		cy.get('.pkpPublication__thoth').contains('a', /^\s*Register\s*$/).scrollIntoView();
		cy.get('.pkpPublication__thoth').contains('a', /^\s*Register\s*$/).should('be.visible');

		// Confirm registration with the disposable publisher's imprint.
		// PKP submits forms as POST with a method override for the PUT endpoint.
		cy.intercept('POST', `**/api/v1/_submissions/${this.book.submissionId}/register`).as('register');
		cy.get('.pkpPublication__thoth').contains('a', /^\s*Register\s*$/).click();
		cy.get('.pkpWorkflow__thothRegisterModal form').should('be.visible').within(() => {
			cy.get('select[name="thothImprintId"]').select('Cypress Imprint');
			cy.contains('button', /^\s*Register\s*$/).click();
		});
		cy.wait('@register').then(({response}) => {
			expect(response.statusCode).to.eq(200);
			expect(response.body.thothWorkId).to.match(/^[a-f0-9-]{36}$/);
		});
		cy.get('.pkpWorkflow__thothRegisterModal form').should('not.exist');
		cy.wait('@registeredFormats').its('response.statusCode').should('eq', 200);
		cy.get('.pkpPublication__thoth').contains('span', /^\s*Active\s*$/).scrollIntoView();
		cy.get('.pkpPublication__thoth').contains('span', /^\s*Active\s*$/).should('be.visible');

		// Check persisted OMP linkage and metadata in the real, isolated Thoth API.
		readRegisteredWork(this.book.key).then((work) => {
			expect(work.workStatus).to.eq('ACTIVE');
			expect(work.workType).to.eq('MONOGRAPH');
			expect(work.titles).to.have.length(2);
			expect(work.titles).to.deep.include({localeCode: 'EN', title: `The ${this.book.title}`,
				subtitle: 'Practices and perspectives', fullTitle: `The ${this.book.title}: Practices and perspectives`,
				canonical: true});
			expect(work.titles).to.deep.include({localeCode: 'PT_BR', title: `A Ciência aberta ${this.book.key}`,
				subtitle: 'Práticas e perspectivas', fullTitle: `A Ciência aberta ${this.book.key}: Práticas e perspectivas`,
				canonical: false});
			expect(work.imprintId).to.eq(this.book.imprintId);
			const metadata = this.book.metadata;
			expect(work).to.include({edition: 1, doi: metadata.doi, publicationDate: '2020-01-01',
				place: 'Manaus', pageCount: 240, imageCount: 12, copyrightHolder: 'Cypress Authors',
				license: 'https://creativecommons.org/licenses/by/4.0/'});
			expect(work.landingPage).to.contain(`/catalog/book/thoth-cypress-${this.book.key}`);
			expect(work.coverUrl).to.contain(`/presses/1/${metadata.coverName}`);
			cy.request(work.coverUrl).its('status').should('eq', 200);
			expect(work.abstracts).to.have.deep.members([
				{localeCode: 'EN', content: '<p>A study of <bold>open science</bold>.</p>', canonical: true, abstractType: 'LONG'},
				{localeCode: 'PT_BR', content: '<p>Um estudo sobre <italic>ciência aberta</italic>.</p>', canonical: false, abstractType: 'LONG'},
			]);
			expect(work.languages).to.deep.eq([{languageCode: 'ENG', languageRelation: 'ORIGINAL'}]);
			expect(work.subjects).to.have.deep.members([
				{subjectType: 'BISAC', subjectCode: 'EDU000000', subjectOrdinal: 1},
				{subjectType: 'THEMA', subjectCode: 'JN', subjectOrdinal: 2},
				{subjectType: 'BIC', subjectCode: 'JN', subjectOrdinal: 3},
				{subjectType: 'LCC', subjectCode: 'Z665', subjectOrdinal: 4},
				{subjectType: 'KEYWORD', subjectCode: 'open research', subjectOrdinal: 5},
				{subjectType: 'KEYWORD', subjectCode: 'open science', subjectOrdinal: 6},
				{subjectType: 'KEYWORD', subjectCode: 'university presses', subjectOrdinal: 7},
			]);
			expect(work.references).to.have.deep.members([
				{referenceOrdinal: 1, unstructuredCitation: 'Example, A. (2019). Open research. https://doi.org/10.5555/example-reference',
					doi: 'https://doi.org/10.5555/example-reference'},
				{referenceOrdinal: 2, unstructuredCitation: 'Example, B. (2018). University presses.', doi: null},
			]);

			// Verify contributor identity and translated biographies.
			expect(work.contributions).to.have.length(1);
			const contribution = work.contributions[0];
			expect(contribution).to.include({contributionType: 'AUTHOR', contributionOrdinal: 1,
				mainContribution: true, firstName: 'Cypress', lastName: metadata.authorFamilyName,
				fullName: `Cypress ${metadata.authorFamilyName}`});
			expect(contribution.contributor).to.deep.eq({firstName: 'Cypress', lastName: metadata.authorFamilyName,
				fullName: `Cypress ${metadata.authorFamilyName}`, orcid: metadata.orcid,
				website: `https://example.org/authors/${this.book.key}`});
			expect(contribution.biographies).to.have.deep.members([
				{localeCode: 'EN', content: '<p>Researcher in <bold>publishing</bold>.</p>', canonical: true},
				{localeCode: 'PT_BR', content: '<p>Pesquisa em <italic>editoração</italic>.</p>', canonical: false},
			]);
			// OMP 3.3/3.4 core has no structured ROR affiliation field.
			expect(contribution.affiliations).to.deep.eq([]);

			// Verify physical and digital formats and the digital edition's identifier, accessibility and location.
			expect(work.publications).to.have.length(3);
			expect(work.publications.map(format => format.publicationType)).to.have.members(['PDF', 'EPUB', 'PAPERBACK']);
			const epub = work.publications.find(format => format.publicationType === 'EPUB');
			expect(epub).to.include({accessibilityException: 'MICRO_ENTERPRISES',
				accessibilityStandard: null, accessibilityAdditionalStandard: null});
			expect(epub.locations).to.deep.eq([{landingPage: work.landingPage,
				fullTextUrl: metadata.remoteUrl.replace('.pdf', '.epub'), locationPlatform: 'OTHER', canonical: true}]);
			const pdf = work.publications.find(format => format.publicationType === 'PDF');
			expect(pdf.isbn.replace(/-/g, '')).to.eq(metadata.isbn);
			expect(pdf).to.include({accessibilityStandard: 'WCAG21AA', accessibilityAdditionalStandard: 'PDF_UA1',
				accessibilityException: null, accessibilityReportUrl: metadata.reportUrl});
			expect(pdf.locations).to.deep.eq([{landingPage: work.landingPage, fullTextUrl: metadata.remoteUrl,
				locationPlatform: 'OTHER', canonical: true}]);

			// Verify the chapter relationship and its own metadata, including the contributor.
			expect(work.relations).to.have.length(1);
			expect(work.relations[0]).to.include({relationType: 'HAS_CHILD', relationOrdinal: 1});
			const chapter = work.relations[0].relatedWork;
			expect(chapter.workId).not.to.eq(work.workId);
			expect(chapter).to.include({workType: 'BOOK_CHAPTER', workStatus: 'ACTIVE', doi: metadata.chapterDoi,
				publicationDate: '2020-01-01', landingPage: work.landingPage, pageInterval: '1-24', firstPage: '1', lastPage: '24'});
			expect(chapter.titles).to.have.deep.members([
				{localeCode: 'EN', title: 'Opening knowledge', subtitle: 'An introduction',
					fullTitle: 'Opening knowledge: An introduction', canonical: true},
				{localeCode: 'PT_BR', title: 'Abrindo o conhecimento', subtitle: 'Uma introdução',
					fullTitle: 'Abrindo o conhecimento: Uma introdução', canonical: false},
			]);
			expect(chapter.abstracts).to.have.deep.members([
				{localeCode: 'EN', content: '<p>Chapter overview.</p>', canonical: true, abstractType: 'LONG'},
				{localeCode: 'PT_BR', content: '<p>Visão geral do capítulo.</p>', canonical: false, abstractType: 'LONG'},
			]);
			expect(chapter.contributions).to.have.length(1);
			expect(chapter.contributions[0]).to.deep.eq({...contribution, mainContribution: false});
		});
		cy.reload();
		cy.get('.pkpPublication__thoth').contains('span', /^\s*Active\s*$/).scrollIntoView();
		cy.get('.pkpPublication__thoth').contains('span', /^\s*Active\s*$/).should('be.visible');
		cy.get('.pkpPublication__thoth').contains('a', /^\s*Register\s*$/).should('not.exist');
	});
});
