INSERT INTO `requirement` (`id`, `award_id`, `number`, `user_number`, `description`, `parent_id`, `n_required`, `required`, `req_type`) VALUES 
(NULL, '280', '1', '1', 'Meet the age requirements. Be a boy who is 11 years old, or one who has completed the fifth grade or earned the Arrow of Light Award and is at least 10 years old, but is not yet 18 years old.', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '2', '2', 'Find a Scout troop near your home. (To find a troop, contact your local Boy Scout Council. The Council name, address and phone number can be found on BSA''s Council Locator Page.)', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '3', '3', 'Complete a Boy Scout application and health history signed by your parent or guardian.', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '4', '4', 'Repeat the Pledge of Allegiance.', '0', '0', 'T', 'Basic Requirement'),  
(NULL, '280', '5', '5', 'Demonstrate the Scout sign, salute, and handshake.', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '6', '6', 'Demonstrate tying the square knot (a joining knot).', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '7', '7', 'Understand and agree to live by the Scout Oath or Promise, Law, motto, and slogan, and the Outdoor Code.', '0', '0', 'T', 'Basic Requirement'), 
(NULL, '280', '8', '8', 'Describe the Scout badge.', '0', '0', 'T', 'Basic Requirement'),
(NULL, '280', '9', '9', 'Complete the pamphlet exercises. With your parent or guardian, complete the exercises in the pamphlet "How to Protect Your Children from Child Abuse: A Parent''s Guide".', '0', '0', 'T', 'Basic Requirement'),
(NULL, '280', '10', '10', 'Participate in a Scoutmaster conference. Turn in your Boy Scout application and health history form signed by your parent or guardian, then participate in a Scoutmaster conference.', '0', '0', 'T', 'Basic Requirement');

ALTER TABLE `user` CHANGE `rank` `rank` ENUM( 'No Rank', 'Scout', 'Boy Scout', 'Tenderfoot', 'Second Class', 'First Class', 'Star', 'Life', 'Eagle', 'Leader', 'Parent' ) NOT NULL DEFAULT 'No Rank';

UPDATE user SET rank = 'Scout' WHERE rank = 'Boy Scout';

ALTER TABLE `user` CHANGE `rank` `rank` ENUM( 'No Rank', 'Scout', 'Tenderfoot', 'Second Class', 'First Class', 'Star', 'Life', 'Eagle', 'Leader', 'Parent' ) NOT NULL DEFAULT 'No Rank'

INSERT INTO  `requirement` (
`id` ,
`award_id` ,
`number` ,
`user_number` ,
`description` ,
`parent_id` ,
`n_required` ,
`required` ,
`req_type`
)
VALUES (
NULL ,  '1',  '3',  '4c',  'Using the EDGE method, teach another person how to tie the square knot.',  '28',  '0',  'T', 'Basic Requirement'
);

UPDATE  `requirement` SET  `description` = 'Explain the importance of the buddy system as it relates to your personal safety on outings and in your neighborhood. Describe what a bully is and how you should respond to one.' WHERE  `requirement`.`id` =35;

UPDATE  `requirement` SET  `description` =  'Demonstrate how to care for someone who is choking.' WHERE  `requirement`.`id` =41;

UPDATE  `requirement` SET  `description` =  'Show first aid for the following: 
Simple cuts and scratches 
Blisters on the hand and foot 
Minor (thermal/heat) burns or scalds (superficial, or first degree) 
Bites and stings of insects and ticks 
Poisonous snakebite 
Nosebleed 
Frostbite and Sunburn' WHERE  `requirement`.`id` =42;

UPDATE  `requirement` SET  `description` = 'Demonstrate Scout spirit by living the Scout Oath (Promise) and Scout Law in your everyday life. Discuss four specific examples of how you have lived the points of the Scout Law in your daily life.' WHERE  `requirement`.`id` =43;

INSERT INTO  `requirement` (
`id` ,
`award_id` ,
`number` ,
`user_number` ,
`description` ,
`parent_id` ,
`n_required` ,
`required` ,
`req_type`
)
VALUES (
NULL ,  '2',  '2',  '2',  'Discuss the principles of Leave No Trace.',  '0',  '0',  'T',  'Basic Requirement'
);

UPDATE requirement SET number = number + 1 WHERE parent_id = 0 AND award_id = 2 AND number >= 2 AND id < 100;

UPDATE  `requirement` SET  `description` = 'On one of these campouts, select your patrol site and sleep in a tent that you pitched. Explain what factors you should consider when choosing a patrol site and where to pitch a tent.' WHERE  `requirement`.`id` =54;

UPDATE  `requirement` SET  `description` =  'Use the tools listed in requirement 3c to prepare tinder, kindling, and fuel for a cooking fire.' WHERE  `requirement`.`id` =56;

UPDATE  `requirement` SET  `description` = 'In an approved place and at an approved time, demonstrate how to build a fire and set up a lightweight stove. Note: Lighting the fire is not required.' WHERE  `requirement`.`id` =58;

UPDATE  `requirement` SET  `description` = 'On one campout, plan and cook one hot breakfast or lunch, selecting foods from the food guide pyramid. Explain the importance of good nutrition. Tell how to transport, store, and prepare the foods you selected.' WHERE  `requirement`.`id` =59;

UPDATE  `requirement` SET  `description` = 'Participate in a flag ceremony for your school, religious institution, chartered organization, community, or troop activity. Explain to your leader what respect is due the flag of the United States.' WHERE  `requirement`.`id` =60;

UPDATE  `requirement` SET  `description` =  'Show what to do for "hurry" cases of stopped breathing, serious bleeding, and ingested poisoning.' WHERE  `requirement`.`id` =64;

UPDATE  `requirement` SET  `description` =  'Demonstrate first aid for the following:
Object in the eye 
Bite of a suspected rabid animal 
Puncture wounds from a splinter, nail, and fishhook 
Serious burns (partial thickness, or second-degree)
Heat exhaustion 
Shock 
Heatstroke, dehydration, hypothermia, and hyperventilation' WHERE  `requirement`.`id` =66;

UPDATE  `requirement` SET  `description` = 'Demonstrate water rescue methods by reaching with your arm or leg, by reaching with a suitable object, and by throwing lines and objects. Explain why swimming rescues should not be attempted when a reaching or throwing rescue is possible, and explain why and how a rescue swimmer should avoid contact with the victim.' WHERE  `requirement`.`id` =70;

UPDATE  `requirement` SET  `description` =  '' WHERE  `requirement`.`id` =71;

INSERT INTO  `requirement` (
`id` ,
`award_id` ,
`number` ,
`user_number` ,
`description` ,
`parent_id` ,
`n_required` ,
`required` ,
`req_type`
)
VALUES (
NULL ,  '2',  '1',  '9a', 'Participate in a school, community, or troop program on the dangers of using drugs, alcohol, and tobacco, and other practices that could be harmful to your health. Discuss your participation in the program with your family, and explain the dangers of substance addictions.',  '71',  '0',  'T',  'Basic Requirement'
), (
NULL ,  '2',  '2',  '9b',  'Explain the three R''s of personal safety and protection.',  '71',  '0',  'T',  'Basic Requirement'
);

UPDATE requirement SET number = number + 1 WHERE award_id = 2 AND number >= 10 AND parent_id = 0;

INSERT INTO  `requirement` (
`id` ,
`award_id` ,
`number` ,
`user_number` ,
`description` ,
`parent_id` ,
`n_required` ,
`required` ,
`req_type`
)
VALUES (
NULL ,  '2',  '10',  '10',  'Earn an amount of money agreed upon by you and your parent, then save at least 50 percent of that money.',  '0',  '0',  'T', 'Basic Requirement'
);

UPDATE  `requirement` SET  `description` = 'Demonstrate Scout spirit by living the Scout Oath (Promise) and Scout Law in your everyday life. Discuss four specific examples (different from those used for Tenderfoot requirement 13) of how you have lived the points of the Scout Law in your daily life.' WHERE  `requirement`.`id` =72;

UPDATE  `requirement` SET  `description` = 'Since joining, have participated in 10 separate troop/patrol activities (other than troop/patrol meetings), three of which included camping overnight. Demonstrate the principles of Leave No Trace on these outings.' WHERE  `requirement`.`id` =48;

UPDATE  `requirement` SET  `description` = 'Help plan a patrol menu for one campout that includes at least one breakfast, one lunch, and one dinner, and that requires cooking at least two of the meals. Tell how the menu includes the foods from the food pyramid and meets nutritional needs.' WHERE  `requirement`.`id` =5;

UPDATE  `requirement` SET  `description` = 'On one campout, serve as your patrol''s cook. Supervise your assistant(s) in using a stove or building a cooking fire. Prepare the breakfast, lunch, and dinner planned in requirement 4a. Lead your patrol in saying grace at the meals and supervise cleanup.' WHERE  `requirement`.`id` =9;

DELETE FROM requirement WHERE id =13 AND award_id =3;

DELETE FROM user_req WHERE req_id = 13;

UPDATE  `requirement` SET  `number` =  '1', `user_number` =  '7a', `description` = 'Discuss when you should and should not use lashings. Then demonstrate tying the timber hitch and clove hitch and their use in square, shear, and diagonal lashings by joining two or more poles or staves together.' WHERE  `requirement`.`id` =14;

UPDATE  `requirement` SET  `number` =  '2', `user_number` =  '7b' WHERE  `requirement`.`id` =15;

UPDATE  `requirement` SET  `description` = 'Demonstrate bandages for a sprained ankle and for injuries on the head, the upper arm, and the collarbone.' WHERE  `requirement`.`id` =18;

UPDATE requirement SET number = number + 1 WHERE award_id = 3 AND number >= 10;

UPDATE  `requirement` SET  `number` =  '10', `user_number` =  '10', `description` = 'Tell someone who is eligible to join Boy Scouts, or an inactive Boy Scout, about your troop''s activities. Invite him to a troop outing, activity, service project, or meeting. Tell him how to join, or encourage the inactive Boy Scout to become active.' WHERE  `requirement`.`id` =3208;

INSERT INTO `requirement` (`id`, `award_id`, `number`, `user_number`, `description`, `parent_id`, `n_required`, `required`, `req_type`) VALUES (NULL, '3', '11', '11', 'Describe the three things you should avoid doing related to the use of the Internet. Describe a cyberbully and how you should respond to one.', '0', '0', 'T', 'Basic Requirement');

UPDATE  `requirement` SET  `number` =  '12', `user_number` =  '12', `description` = 'Demonstrate Scout spirit by living the Scout Oath (Promise) and Scout Law in your everyday life. Discuss four specific examples (different from those used in Tenderfoot requirement 13 and Second Class requirement 11) of how you have lived the points of the Scout Law in your daily life.' WHERE  `requirement`.`id` =25;

UPDATE  `requirement` SET  `user_number` =  '13' WHERE  `requirement`.`id` =26;

UPDATE  `requirement` SET  `user_number` =  '14' WHERE  `requirement`.`id` =27;

UPDATE requirement SET description = 'While a First Class Scout, serve actively for four months in one or more of the following positions of responsibility (or carry out a Scoutmaster-assigned leadership project to help the troop):

Boy Scout troop:
Patrol Leader, Assistant Senior Patrol Leader, Senior Patrol Leader, Venture Patrol Leader, Troop Guide, Order of the Arrow Troop Representative, Den Chief, Scribe, Librarian, Historian, Quartermaster, Bugler, Junior Assistant Scoutmaster, Chaplain Aide, Instructor, Troop Webmaster, or Leave No Trace Trainer.

Varsity Scout team:
Captain, Cocaptain, Program Manager, Squad Leader, Team Secretary, Order of the Arrow Team Representative, Librarian, Historian, Quartermaster, Chaplain Aide, Instructor, Den Chief, Team Webmaster, or Leave No Trace Trainer.

Venturing crew/Sea Scout ship:
President, Vice President, Secretary, Treasurer, Den Chief, Quartermaster, Historian, Guide, Boatswain, Boatswain\'s Mate, Yeoman, Purser, Storekeeper, Crew/Ship Webmaster, or Leave No Trace Trainer.' WHERE id = 3132;

UPDATE `requirement` SET number = number + 1 WHERE award_id = 5 AND number > 5;

INSERT INTO `requirement` (`id`, `award_id`, `number`, `user_number`, `description`, `parent_id`, `n_required`, `required`, `req_type`) VALUES (NULL, '5', '6', '6', 'While a Star Scout, use the EDGE method to teach a younger Scout the skills from ONE of the following six choices, so that he is prepared to pass those requirements to his unit leader''s satisfaction.
a. Second Class - 7a and 7c (first aid)
b. Second Class - 1a (outdoor skills)
c. Second Class - 3c, 3d, 3e, and 3f (cooking/camping)
d. First Class - 8a, 8b, 8c, and 8d (first aid)
e. First Class - 1, 7a, and 7b (outdoor skills)
f. First Class - 4a, 4b, and 4d (cooking/camping)', '0', '0', 'T', 'Basic Requirement');

UPDATE  `requirement` SET  `description` = 'Demonstrate that you live by the principles of the Scout Oath and Law in your daily life. List the names of individuals who know you personally and would be willing to provide a recommendation on your behalf, including parents/guardians, religious, educational, and employer references.' WHERE  `requirement`.`id` =3143;

UPDATE  `requirement` SET  `description` =  'Earn a total of 21 merit badges (10 more than you already have), including the following:
a. First Aid
b. Citizenship in the Community
c. Citizenship in the Nation
d. Citizenship in the World
e. Communications
f. Personal Fitness
g. Emergency Preparedness OR Lifesaving*
h. Environmental Science
i. Personal Management
j. Swimming OR Hiking OR Cycling*
k. Camping, and
l. Family Life
* You must choose only one merit badge listed in items g and j. If you have earned more than one of the badges listed in items g and j, choose one and list the remaining badges to make your total of 21.' WHERE  `requirement`.`id` =3144;

UPDATE  `requirement` SET  `description` =  'While a Life Scout, serve actively for a period of six months in one or more of the following positions of responsibility:

Boy Scout troop:
Patrol Leader, Assistant Senior Patrol Leader, Senior Patrol Leader, Venture Patrol Leader, Troop Guide, Order of the Arrow Troop Representative, Den Chief, Scribe, Librarian, Historian, Quartermaster, Junior Assistant Scoutmaster, Chaplain Aide, Instructor, Webmaster, or Leave No Trace Trainer.

Varsity Scout team:
Captain, Cocaptain, Program Manager, Squad Leader, Team Secretary, Order of the Arrow Team Representative, Librarian, Historian, Quartermaster, Chaplain Aide, Instructor, Den Chief, Webmaster, or Leave No Trace Trainer.

Venturing Crew/Ship:
President, Vice President, Secretary, Treasurer, Quartermaster, Historian, Den Chief, Guide, Boatswain, Boatswain''s Mate, Yeoman, Purser, Storekeeper, Webmaster, or Leave No Trace Trainer.' WHERE  `requirement`.`id` =3145;

UPDATE  `requirement` SET  `description` = 'While a Life Scout, plan, develop, and give leadership to others in a service project helpful to any religious institution, any school, or your community. (The project should benefit an organization other than Boy Scouting.) The project plan must be approved by the organization benefiting from the effort, your Scoutmaster and troop committee, and the council or district before you start. You must use the Eagle Scout Leadership Service Project Workbook, BSA publication No. 521-927, in meeting this requirement.' WHERE  `requirement`.`id` =3146;

INSERT INTO `requirement` (`id`, `award_id`, `number`, `user_number`, `description`, `parent_id`, `n_required`, `required`, `req_type`) VALUES (NULL, '6', '8', '', 'Official notes (part of the rank requirements)
AGE REQUIREMENT ELIGIBILITY. Merit badges, badges of rank, and Eagle Palms may be earned by a registered Boy Scout, Varsity Scout, or Venturer. He may earn these awards until his 18th birthday. Any Venturer who achieved the First Class rank as a Boy Scout in a troop or Varsity Scout in a team may continue working for the Star, Life, and Eagle Scout ranks and Eagle Palms while registered as a Venturer up to his 18th birthday. Scouts and Venturers who have completed all requirements prior to their 18th birthday may be reviewed within three months after that date with no explanation. Boards of Review conducted between three and six months after the candidate''s 18th birthday must be preapproved by the local council. A statement by an adult explaining the reason for the delay must be attached to the Eagle Scout Rank Application when it is submitted to the Eagle Scout Service. The Eagle Scout Service at the national office must be contacted for procedures to follow if a board of review is to be conducted more than six months after a candidate''s 18th birthday.
If you have a permanent physical or mental disability, you may become an Eagle Scout by qualifying for as many required merit badges as you can and qualifying for alternative merit badges for the rest. If you seek to become an Eagle Scout under this procedure, you must submit a special application to your local council service center. Your application must be approved by your council advancement committee before you can work on alternative merit badges.', '0', '0', 'F', 'Comment');
