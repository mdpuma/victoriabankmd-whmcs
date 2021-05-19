-- --------------------------------------------------------

--
-- Table structure for table `mod_victoriabank_transactions`
--

CREATE TABLE `mod_victoriabank_transactions` (
  `id` int(11) NOT NULL,
  `pending` int(11) NOT NULL,
  `bin` int(11) NOT NULL,
  `card` varchar(18) NOT NULL,
  `rrn` bigint(20) NOT NULL,
  `text` varchar(64) NOT NULL,
  `orderid` int(11) NOT NULL,
  `invoiceid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mod_victoriabank_transactions`
--
ALTER TABLE `mod_victoriabank_transactions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mod_victoriabank_transactions`
--
ALTER TABLE `mod_victoriabank_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
