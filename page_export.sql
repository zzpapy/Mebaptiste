--
-- PostgreSQL database dump
--

\restrict D9XIwcXsT24n1GzwZsVSMwk7PaKzyuj3JgOVPaa0P7s0sSiw5uGF4XhyZTrGDDG

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: page; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.page (id, title, slug, content, meta_description, is_published) VALUES (3, 'accueil', 'accueil', '<div>Bienvenue au cabinet de Maître Baptiste Lebrou, avocat au barreau de Strasbourg.<br><br>Fort de plus de dix ans d''expérience, le cabinet accompagne particuliers et entreprises dans leurs démarches juridiques, avec rigueur, écoute et disponibilité.<br><br>Le cabinet intervient notamment en droit de la famille, droit pénal, droit du travail, droit immobilier et droit des affaires.<br><br></div>', NULL, true);
INSERT INTO public.page (id, title, slug, content, meta_description, is_published) VALUES (4, 'A propos', 'a-propos', '<div>Maître Baptiste Lebrou a prêté serment le 9 janvier 2012 au barreau de Strasbourg. Depuis plus de dix ans, il exerce en cabinet individuel au 39 avenue des Vosges, à Strasbourg.<br><br>Son approche du droit privilégie l''écoute et la recherche de solutions adaptées à chaque situation, qu''il s''agisse d''une procédure amiable ou d''un contentieux devant les juridictions.<br><br>Le cabinet propose ses services en français, mais aussi en anglais, espagnol et italien.<br><br><br></div>', NULL, true);
INSERT INTO public.page (id, title, slug, content, meta_description, is_published) VALUES (5, 'Domaines d''expertise', 'domaines-dexpertise', '<div>Droit de la famille<br>Divorce, garde d''enfants, pension alimentaire, succession et protection des personnes vulnérables.<br><br>Droit pénal<br>Assistance dès la garde à vue jusqu''au procès, pour toute affaire correctionnelle ou criminelle.<br><br>Droit du travail<br>Contrats de travail, licenciements, harcèlement et litiges avec l''employeur.<br><br>Droit immobilier<br>Achat, vente, location, construction et gestion de copropriété.<br><br>Droit des affaires<br>Création d''entreprise, contrats commerciaux, concurrence et contentieux.<br><br></div>', NULL, true);


--
-- Name: page_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.page_id_seq', 5, true);


--
-- PostgreSQL database dump complete
--

\unrestrict D9XIwcXsT24n1GzwZsVSMwk7PaKzyuj3JgOVPaa0P7s0sSiw5uGF4XhyZTrGDDG

