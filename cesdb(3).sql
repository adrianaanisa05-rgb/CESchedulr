-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2026 at 02:06 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cesdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `activity_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity`
--

INSERT INTO `activity` (`id`, `event_id`, `title`, `description`, `activity_date`, `start_time`, `end_time`, `created_at`, `user_id`) VALUES
(1, 2, 'Ceremonial speech', 'A speech that will be given by miss aliya to commemorate the marathon', '2025-12-19', '06:00:00', '08:00:00', '2025-12-19 13:08:08', 1),
(3, 2, 'Breakfast distribution', 'Before award ceremony. Each participant will be given food', '2025-12-19', '14:00:00', '00:00:00', '2025-12-19 13:17:40', 1),
(4, 2, 'Marathon registration', 'Contestant register to enter the marathon ', '2025-12-18', '08:00:00', '10:00:00', '2025-12-22 07:55:26', 1),
(5, 2, 'test', 'testing', '2025-12-19', '08:30:00', '11:00:00', '2025-12-22 08:19:12', 1),
(6, 2, 'test2', 'testing', '2025-12-19', '11:30:00', '12:00:00', '2025-12-22 08:46:53', 1),
(7, 2, 'test 3', 'testing', '2025-12-19', '12:05:00', '13:00:00', '2025-12-22 08:47:56', 1),
(8, 2, 'testing 4', 'test 4', '2025-12-19', '13:01:00', '13:59:00', '2025-12-22 08:48:45', 1),
(11, 15, 'Opening ceremony', 'Ceremonial speech by the head of the event', '2026-01-11', '02:00:00', '03:00:00', '2026-01-10 09:38:12', 6),
(12, 15, 'Prepping station', 'Station preparation for participant', '2026-01-11', '03:05:00', '04:00:00', '2026-01-10 09:39:16', 6);

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `id` int(11) NOT NULL,
  `club_name` varchar(255) NOT NULL,
  `club_description` text DEFAULT NULL,
  `club_email` varchar(255) DEFAULT NULL,
  `club_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club`
--

INSERT INTO `club` (`id`, `club_name`, `club_description`, `club_email`, `club_phone`, `status`, `created_by`, `created_at`) VALUES
(1, 'Tech Innovator Club', 'A club focused on innovation, coding, and technology events.', 'techclub@gmail.com', '+60123456789', 'active', 1, '2026-01-06 19:44:09'),
(2, 'Computer Club', 'A computer club', 'cc@gmail.com', '0213456321', 'active', 1, '2026-01-07 08:30:46'),
(3, 'Taekwondo Club', 'Taekwondo representative club', 'Taekwondo@gmail.com', '0193456732', 'active', 1, '2026-01-07 20:36:39'),
(4, 'Cooking club', 'cooking club', 'cook@gmail.com', '0192345321', 'active', 1, '2026-01-07 21:11:09');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `event_id`, `user_id`, `comment`, `created_at`, `updated_at`) VALUES
(3, 1, 1, 'please make sure to fill in all of your details in the table above up here', '2026-01-05 06:53:12', '2026-01-05 06:53:24'),
(8, 14, 6, 'hello', '2026-01-10 01:37:53', NULL),
(9, 13, 9, 'hello', '2026-01-10 17:18:46', NULL),
(10, 13, 9, 'Is there will be food inside the venue?', '2026-01-10 17:53:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `event_capacity` int(11) NOT NULL,
  `event_image` varchar(255) DEFAULT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `event_status` enum('public','private','concluded') NOT NULL DEFAULT 'public',
  `contact_number` text DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_description`, `event_date`, `end_date`, `event_capacity`, `event_image`, `venue_id`, `user_id`, `club_id`, `event_status`, `contact_number`, `approval_status`, `admin_remark`) VALUES
(1, 'Gamescom', 'the world\'s largest gaming festival and a leading business platform for the video game industry, held annually in Cologne, Germany. It brings together the global gaming community, developers, publishers, and fans to experience new games, attend esports tournaments, and participate in other events like cosplay and shows. It is organized jointly by Koelnmesse and the German Games Industry Association.', '2025-12-17 16:50:00', '2025-12-17 18:00:00', 20, '1764628302_bf25a9f3.jpg', 1, 1, 2, 'concluded', '010-479-8852 - Azri ', 'approved', 'You may proceed with the event'),
(2, 'Marathon', 'The marathon is a long-distance foot race with a distance of 42.195 kilometres ( c. 26 mi 385 yd), usually run as a road race, but the distance can be covered on trail routes. The marathon can be completed by running or with a run/walk strategy. There are also wheelchair divisions.', '2025-07-09 17:00:00', NULL, 25, NULL, NULL, 1, NULL, 'public', NULL, 'approved', 'Please add venue details in the future and contact details. And add a poster for this event'),
(3, 'Graphic design competition', 'The Global Creative Canvas is an international graphic design competition inviting designers, students, and creative professionals from around the world to showcase their talent, ingenuity, and unique artistic vision. This competition is a platform for innovative design that pushes boundaries, tells compelling stories, and demonstrates mastery over the visual medium.', '2025-12-19 14:10:00', '2025-12-19 14:20:00', 50, '1765036601_8c19bacd.png', 1, 1, 2, 'concluded', '010-451-7789 Hamton', 'approved', 'Please add venue details'),
(4, 'Private meeting', 'idk for fun', '2026-01-12 12:00:00', '2026-01-13 12:00:00', 10, NULL, NULL, 1, 1, 'private', '', 'approved', 'Please add more information regarding venue and event'),
(7, 'Camping In Mount Kinabalu', 'invites people to escape the everyday into nature for temporary outdoor living, focusing on activities like hiking, campfires, games, and stargazing, offering a chance to disconnect, connect with nature, build self-reliance, and enjoy simple joys like s\'mores and storytelling under the stars, often within parks or designated campsites', '2025-12-29 09:00:00', NULL, 10, '1766740536_d12b9b76.png', NULL, 1, 3, 'public', '010-351-7757 -Azri', 'approved', 'Please add venue information'),
(8, 'Test event', 'Testing', '2025-12-31 18:17:00', '2025-12-31 19:17:00', 6, NULL, NULL, 1, 1, 'concluded', '', 'approved', 'Please add event description and venue information'),
(12, 'Spring Coding Bootcamp', 'An intensive 1-day bootcamp introducing beginners to web development, including HTML, CSS, and JavaScript. Lunch and refreshments will be provided.', '2026-03-11 09:00:00', '2026-03-20 09:00:00', 150, NULL, 7, 6, 1, 'public', '019-356-8290 Brianna', 'pending', 'Please add a venue image and add activity tentative once approved'),
(13, 'Cry of laughter', 'Festival Event', '2026-03-04 06:00:00', '2026-03-05 07:00:00', 20, '1767964146_748647b9.jpeg', 4, 6, 1, 'public', '012345872-Rahim 019-766-878- Zeke 019-453-5567 - Ryan', 'approved', 'Please add a poster and add activity details after'),
(14, 'Coding Crush', 'coding club', '2026-01-09 12:00:00', '2026-01-23 12:00:00', 20, '1767898264_7b93104e.jpeg', 4, 6, 1, 'concluded', 'Rahim- 019-8783-560 Zeke-018-445-7767', 'approved', 'Please add venue image and then approve participant'),
(15, 'Cookie Bakeoff', 'Cookie making event', '2026-01-11 12:00:00', '2026-01-12 12:00:00', 20, '1767960548_92f447bc.jpg', 4, 6, 4, 'public', '', 'approved', 'This event has been approved and meets all condition'),
(16, 'Art & Craft Fair', 'Local artists showcase and sell their artwork', '2026-03-05 10:00:00', '2026-03-05 18:00:00', 10, '1768037385_dcd03f01.png', NULL, 6, 1, 'public', '019-456-7789 Brian, 019-778-6678 Brianna', 'pending', NULL),
(17, 'Test test', 'Testing testing', '2026-04-07 17:54:00', '2026-04-07 18:00:00', 20, NULL, NULL, 6, 2, 'public', '', 'approved', 'Please add venue details'),
(18, 'Testing 2', 'Testing 2', '2026-02-25 18:54:00', '2026-02-26 18:54:00', 20, NULL, 7, 6, 1, 'public', '', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `type` enum('announcement','invitation','action') DEFAULT 'announcement',
  `response` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `event_id`, `sender_id`, `receiver_id`, `title`, `message`, `created_at`, `is_read`, `type`, `response`) VALUES
(1, 1, 1, 1, 'Testing', 'Please bring pencil', '2025-12-12 10:59:43', 1, 'announcement', 'pending'),
(3, 1, 1, 1, 'Update on what to bring in the event', 'Please bring your own costume', '2025-12-16 18:52:37', 1, 'announcement', 'pending'),
(7, 1, 1, 1, 'another test', 'testing', '2025-12-16 19:28:32', 1, 'announcement', 'pending'),
(9, 1, 1, 1, 'serdtfyguh', 'dtrfyguhijk', '2025-12-23 03:12:00', 1, 'announcement', 'pending'),
(12, 1, 1, 4, 'serdtfyguh', 'dtrfyguhijk', '2025-12-23 03:12:00', 0, 'announcement', 'pending'),
(14, 7, 5, 5, 'Event Left', 'You have successfully left the event.', '2025-12-26 10:25:01', 1, 'announcement', 'pending'),
(15, 1, 1, 1, 'Extra supplement', 'Please bring a bottled water', '2025-12-26 10:26:12', 1, 'announcement', 'pending'),
(17, 1, 1, 4, 'Extra supplement', 'Please bring a bottled water', '2025-12-26 10:26:12', 0, 'announcement', 'pending'),
(20, 2, 1, 1, 'Event approved: Marathon', 'Your event \"Marathon\" has been approved. Admin Remark: Please add venue details in the future and contact details. And add a poster for this event', '2025-12-26 17:11:37', 1, 'announcement', 'pending'),
(21, 1, 1, 1, 'Event approved: Gamescom', 'Your event \"Gamescom\" has been approved. Admin Remark: You may proceed with the event', '2025-12-26 21:19:54', 1, 'announcement', 'pending'),
(22, 3, 1, 1, 'Event approved: Graphic design competition', 'Your event \"Graphic design competition\" has been approved. Admin Remark: Please add venue details', '2025-12-26 21:34:10', 1, 'announcement', 'pending'),
(23, 7, 1, 1, 'Event approved: Camping In Mount Kinabalu', 'Your event \"Camping In Mount Kinabalu\" has been approved. Admin Remark: Please add venue information', '2025-12-29 15:01:54', 1, 'announcement', 'pending'),
(24, 8, 1, 1, 'Event approved: Test event', 'Your event \"Test event\" has been approved. Admin Remark: Please add event description and venue information', '2025-12-29 15:02:11', 1, 'announcement', 'pending'),
(25, 4, 1, 1, 'Event approved: Private meeting', 'Your event \"Private meeting\" has been approved. Admin Remark: Please add more information regarding venue and event', '2025-12-29 15:02:39', 1, 'announcement', 'pending'),
(27, 1, 1, 4, 'Removed from Event', 'You have been removed from the event.\n\nReason:\nparticipant didn\'t respond to phone calls', '2025-12-31 11:30:48', 0, 'announcement', 'pending'),
(28, 1, 1, 2, 'Removed from Event', 'You have been removed from the event.\n\nReason:\nYou didn\'t fill all details needed in the event', '2025-12-31 12:03:07', 1, 'action', 'pending'),
(29, 1, 1, 1, 'I hate this event', 'icl i hate this event', '2025-12-31 13:39:42', 1, 'announcement', 'pending'),
(37, 1, 5, 5, 'Event Left', 'You have successfully left the event.', '2026-01-06 05:41:00', 1, 'action', 'pending'),
(42, 12, 1, 6, 'Event approved: Spring Coding Bootcamp', 'Your event \"Spring Coding Bootcamp\" has been approved. Admin Remark: Please add a venue image and add activity tentative once approved', '2026-01-07 08:54:26', 1, 'announcement', 'pending'),
(43, 13, 1, 6, 'Event approved: Coding Crunch', 'Your event \"Coding Crunch\" has been approved. Admin Remark: Please add a poster and add activity details after', '2026-01-07 11:28:27', 1, 'announcement', 'pending'),
(46, 14, 1, 6, 'Event approved: Computer Showcase Event', 'Your event \"Computer Showcase Event\" has been approved. Admin Remark: Please add venue image and then approve participant', '2026-01-09 08:12:26', 1, 'announcement', 'pending'),
(47, 13, 6, 5, 'Participation Request Accepted', 'Your request to join the event \"Cry of laughter\" has been accepted.', '2026-01-09 16:10:13', 1, 'action', 'accepted'),
(48, 14, 6, 5, 'Participation Request Rejected', 'Your request to join the event \"Coding Crush\" has been rejected.', '2026-01-09 16:11:20', 1, 'action', 'declined'),
(49, 13, 6, 9, 'Participation Request Accepted', 'Your request to join the event \"Cry of laughter\" has been accepted.', '2026-01-10 09:16:48', 1, 'action', 'accepted'),
(50, 13, 6, 5, 'Please bring extra item', 'Please bring umbrella', '2026-01-10 09:21:54', 0, 'announcement', 'pending'),
(51, 13, 6, 9, 'Please bring extra item', 'Please bring umbrella', '2026-01-10 09:21:54', 1, 'announcement', 'pending'),
(52, 14, 6, 9, 'Invitation to join: Coding Crush', 'You are invited to join the event \'Coding Crush\'. Please respond to this invitation.', '2026-01-10 09:33:40', 1, 'invitation', 'accepted'),
(53, 14, 6, 9, 'Participation Request Accepted', 'Your request to join the event \"Coding Crush\" has been accepted.', '2026-01-10 09:35:03', 1, 'action', 'accepted'),
(54, 14, 6, 5, 'Event Concluded', 'The event \'Coding Crush\' has been concluded.', '2026-01-10 09:35:32', 0, 'announcement', 'pending'),
(55, 14, 6, 9, 'Event Concluded', 'The event \'Coding Crush\' has been concluded.', '2026-01-10 09:35:32', 1, 'announcement', 'pending'),
(56, 15, 1, 6, 'Event approved: Cookie Bakeoff', 'Your event \"Cookie Bakeoff\" has been approved. Admin Remark: This event has been approved and meets all condition', '2026-01-10 09:41:04', 0, 'announcement', 'pending'),
(57, 13, 6, 4, 'Invitation to join: Cry of laughter', 'You are invited to join the event \'Cry of laughter\'. Please respond to this invitation.', '2026-01-10 09:57:44', 0, 'invitation', 'pending'),
(58, 13, 6, 5, 'Greetings', 'Hello!', '2026-01-10 09:58:05', 0, 'announcement', 'pending'),
(59, 13, 6, 9, 'Greetings', 'Hello!', '2026-01-10 09:58:05', 1, 'announcement', 'pending'),
(60, 17, 1, 6, 'Event approved: Test test', 'Your event \"Test test\" has been approved. Admin Remark: Please add venue details', '2026-01-10 11:55:52', 0, 'announcement', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `participant`
--

CREATE TABLE `participant` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `participant_status` enum('attend','absent','late') NOT NULL DEFAULT 'attend',
  `gender` enum('male','female') DEFAULT NULL,
  `joined_date` datetime DEFAULT current_timestamp(),
  `participant_phone` varchar(25) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `request_status` enum('pending','accepted','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participant`
--

INSERT INTO `participant` (`id`, `event_id`, `user_id`, `full_name`, `participant_status`, `gender`, `joined_date`, `participant_phone`, `remarks`, `request_status`) VALUES
(1, 1, 1, 'Azri Shahmi ', 'attend', 'male', '2025-12-10 18:08:39', '+60123456789', NULL, 'accepted'),
(2, 3, 1, '', 'attend', NULL, '2025-12-11 22:33:03', NULL, NULL, 'accepted'),
(3, 2, 1, '', 'attend', NULL, '2025-12-12 19:26:10', NULL, NULL, 'accepted'),
(12, 7, 2, '', 'attend', NULL, '2025-12-30 06:07:28', NULL, NULL, 'pending'),
(18, 13, 5, '', 'attend', NULL, '2026-01-10 00:09:40', NULL, NULL, 'accepted'),
(19, 14, 5, '', 'attend', NULL, '2026-01-10 00:10:58', NULL, NULL, 'rejected'),
(20, 13, 9, 'Nor Tammy', 'late', 'female', '2026-01-10 17:13:25', '0137657789', 'I\'m late due to traffic jam', 'accepted'),
(21, 14, 9, '', 'attend', NULL, '2026-01-10 17:34:36', NULL, NULL, 'accepted'),
(22, 7, 9, '', 'attend', NULL, '2026-01-10 17:52:24', NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gmail` varchar(255) NOT NULL,
  `user_type` enum('participant','organizer','admin') NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `users_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `gmail`, `user_type`, `phone_number`, `users_image`) VALUES
(1, 'azri', 'Azri@123', 'azrishahmi150@gmail.com', 'admin', '0103517757', '1767685822_d5e77cbe.png'),
(2, 'patrick', 'pat123', 'pat@gmail.com', 'organizer', '60123457898', '1765791292_60d16c96.png'),
(4, 'tron', 'tron123', 'tron@gmail.com', 'participant', '0124567913', NULL),
(5, 'gray', 'gray123', 'gray@gmail.com', 'participant', '177328910', NULL),
(6, 'Adam', 'adam123', 'adam@gmail.com', 'organizer', '109940231', '1768026235_c77762da.png'),
(8, 'JohnDoe', 'Passw0rd!', 'john@gmail.com', 'participant', '0123456789', NULL),
(9, 'Tammy', 'Tammy@13', 'tammy@gmail.com', 'participant', '0104879984', '1768037047_1cdf4512.png');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `venue_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `venue_name` varchar(255) NOT NULL,
  `venue_address` varchar(255) NOT NULL,
  `venue_city` varchar(255) NOT NULL,
  `venue_postcode` varchar(20) NOT NULL,
  `venue_capacity` int(11) NOT NULL,
  `remark` text DEFAULT NULL,
  `venue_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`venue_id`, `user_id`, `venue_name`, `venue_address`, `venue_city`, `venue_postcode`, `venue_capacity`, `remark`, `venue_image`, `created_at`) VALUES
(1, 1, 'Kuala Lumpur Convention Centre', 'Kuala Lumpur Convention Centre, Kuala Lumpur, 50088 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', 'Kuala Lumpur', '50088', 50, 'Chairs and table are provided. There are also meals provided', '1765971655_130fc5e0.jpeg', '2025-12-17 11:40:55'),
(4, 6, 'The Timber Space', 'Jalan 26/70a, Desa Sri Hartamas, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur', 'Kuala Lumpur', '50480', 100, 'Food is provided', NULL, '2026-01-07 08:33:00'),
(5, 6, 'Zen Garden', 'Thousand street', 'Kuala Lumpur', '59945', 10, 'Seats are prepared', NULL, '2026-01-09 12:08:16'),
(7, 6, 'The Ark Event Space', 'E-1-14 & E-1-16, Block E, Jalan Sri Hartamas 1, Taman Sri Hartamas, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur 50480', 'Kuala Lumpur', '50480', 70, 'This event provided food and beverages', '1768037497_211ae67b.png', '2026-01-10 09:31:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_name` (`club_name`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_venue` (`venue_id`),
  ADD KEY `fk_events_club` (`club_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `participant`
--
ALTER TABLE `participant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `gmail` (`gmail`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`venue_id`),
  ADD KEY `fk_venue_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity`
--
ALTER TABLE `activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `club`
--
ALTER TABLE `club`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `participant`
--
ALTER TABLE `participant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity`
--
ALTER TABLE `activity`
  ADD CONSTRAINT `activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `club`
--
ALTER TABLE `club`
  ADD CONSTRAINT `club_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_events_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_venue` FOREIGN KEY (`venue_id`) REFERENCES `venue` (`venue_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `participant`
--
ALTER TABLE `participant`
  ADD CONSTRAINT `participant_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participant_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venue`
--
ALTER TABLE `venue`
  ADD CONSTRAINT `fk_venue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
